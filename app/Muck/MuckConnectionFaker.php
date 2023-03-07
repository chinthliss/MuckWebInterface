<?php

namespace App\Muck;

use Carbon\Carbon;
use Error;
use Illuminate\Support\Facades\Log;
use Database\Seeders\DatabaseSeeder;

class MuckConnectionFaker implements MuckConnection
{
    /**
     * Collection of fixed fake data to run tests against.
     * Late loaded to avoid it being populated for every test.
     * @var MuckDbref[]
     */
    private array $muckDatabase = [];

    private function populateDatabaseIfRequired()
    {
        if (count($this->muckDatabase)) return;
        $this->muckDatabase[] = new MuckDbref(1234, 'TestCharacter', 'p', Carbon::now(), Carbon::now(), [
            'accountId' => strval(DatabaseSeeder::$normalUserAccountId),
            'level' => '10',
            'approved' => '1'
        ]);
        $this->muckDatabase[] = new MuckDbref(1235, 'TestAltCharacter', 'p', Carbon::now(), Carbon::now(), [
            'accountId' => strval(DatabaseSeeder::$normalUserAccountId),
            'level' => '2',
            'approved' => '1'
        ]);
        $this->muckDatabase[] = new MuckDbref(1236, 'unapprovedCharacter', 'p', Carbon::now(), Carbon::now(), [
            'accountId' => strval(DatabaseSeeder::$normalUserAccountId),
            'level' => '1'
        ]);
        $this->muckDatabase[] = new MuckDbref(1240, 'otherUsersCharacter', 'p', Carbon::now(), Carbon::now(), [
            'accountId' => strval(DatabaseSeeder::$secondNormalUserAccountId),
            'level' => '1'
        ]);
        $this->muckDatabase[] = new MuckDbref(1300, 'adminCharacter', 'p', Carbon::now(), Carbon::now(), [
            'accountId' => strval(DatabaseSeeder::$adminUserAccountId),
            'staffLevel' => '2'
        ]);

    }

    // Converts a dbref into the string the muck would return, in order to test parsing of such.
    private function dbrefToMuckResponse(MuckDbref $dbref): string
    {
        // Format - dbref,creationTimestamp,typeFlag,"name", .. otherProperties
        $piecesArray = [$dbref->dbref, $dbref->createdTimestamp->getTimestamp(), $dbref->lastUsedTimestamp?->getTimestamp(), $dbref->typeFlag, $dbref->name];
        if ($dbref->accountId()) $piecesArray[] = "\"accountId=" . $dbref->accountId() . "\"";
        if ($dbref->level()) $piecesArray[] = "\"level=" . $dbref->level() . "\"";
        foreach ($dbref->properties as $key => $value) {
            $piecesArray[] = "\"$key=$value\"";
        }
        return join(',', $piecesArray);
    }

    public function fake_getByDbref(array $data): string
    {
        $dbrefWanted = $data['dbref'];
        foreach ($this->muckDatabase as $dbref) {
            if ($dbref->dbref == $dbrefWanted) return $this->dbrefToMuckResponse($dbref);
        }
        return '';
    }

    public function fake_getByPlayerName(array $data): string
    {
        $nameWanted = $data['name'];
        foreach ($this->muckDatabase as $dbref) {
            if (strtolower($dbref->name) == strtolower($nameWanted)) return $this->dbrefToMuckResponse($dbref);
        }
        return '';
    }

    public function fake_getByApiToken(array $data): string
    {
        throw new Error("Not implemented.");
    }

    public function fake_getCharacters(array $data): string
    {
        $accountWanted = $data['aid'];
        $characters = [];
        foreach ($this->muckDatabase as $dbref) {
            if ($dbref->accountId() == $accountWanted) $characters[] = $dbref;
        }
        return join(chr(13) . chr(10), array_map(function ($character) {
            return $this->dbrefToMuckResponse($character);
        }, $characters));
    }

    public function fake_findAccountsByCharacterName(array $data): string
    {
        $name = $data['name'];
        $accountIds = [];
        foreach ($this->muckDatabase as $dbref) {
            if (str_contains(strtolower($dbref->name), strtolower($name))) $accountIds[] = $dbref->accountId();
        }
        return join(chr(13) . chr(10), array_unique($accountIds));
    }

    public function fake_validateCredentials(array $data): string
    {
        // All passwords are 'muckpassword' during faking.
        $password = $data['password'];
        return ($password == 'muckpassword');
    }

    public function fake_findProblemsWithCharacterName(array $data): string
    {
        $name = $data['name'];
        if (strtolower($name) == 'test') return 'That name is a test.';
        if (str_contains($name, ' ')) return 'That name contains a space.';
        return '';
    }

    public function fake_findProblemsWithCharacterPassword(array $data): string
    {
        $password = $data['password'];
        if (strtolower($password) == 'test') return 'That password is a test.';
        return '';
    }

    public function fake_changeCharacterPassword(array $data): string
    {
        return "OK";
    }

    public function fake_getCharacterSlotState(array $data): string
    {
        return "3,10";
    }

    public function fake_buyCharacterSlot(array $data): string
    {
        // return "ERROR,something";
        return "OK,5,50";
    }

    public function fake_createCharacter(array $data): string
    {
        if ($data['name'] == 'fake') return 'ERROR|Character called fake not allowed.';
        return 'OK|1236|testword';
    }

    public function fake_getCharacterInitialSetupConfiguration(array $data): string
    {
        $config = [
            "factions" => [
                [
                    "name" => "FakeFaction1",
                    "description" => "The first fake faction for testing."
                ],
                [
                    "name" => "FakeFaction2",
                    "description" => "The second fake faction for testing. This line break shouldn't be parsed:<br/>"
                ],
                [
                    "name" => "Longer named faction 3",
                    "description" => "The third faction with some differences so it's actually possible to check scaling. Along with some extra text to effectively act as a second line."
                ]
            ],
            "flaws" => [
                [
                    "name" => "FakeFlaw1",
                    "description" => "The first fake flaw for testing.",
                    "excludes" => []
                ],
                [
                    "name" => "FakeFlaw2",
                    "description" => "The second fake flaw for testing.",
                    "excludes" => ["FakeFlaw3"]
                ],
                [
                    "name" => "FakeFlaw2bOrNot2b",
                    "description" => "Somewhere between the second and third flaw, complete with a terrible pun in the name.",
                    "excludes" => ["FakeFlaw3"]
                ],
                [
                     "name" => "FakeFlaw3",
                    "description" => "The third fake flaw for testing.",
                    "excludes" => ["FakeFlaw2"]
                ],
                [
                    "name" => "Unselectable Flaw",
                    "description" => "Picking this should cause validation to fail.",
                    "excludes" => []
                ]
            ],
            "perks" => [
                [
                    "name" => "FakePerk1",
                    "description" => "The first fake perk for testing.",
                    "category" => 'appearance',
                    "excludes" => []
                ],
                [
                    "name" => "FakePerk2",
                    "description" => "The second fake perk for testing.",
                    "category" => 'appearance',
                    "excludes" => ["FakePerk3"]
                ],
                [
                    "name" => "FakePerk3",
                    "description" => "The third fake perk for testing.",
                    "category" => 'appearance',
                    "excludes" => ["FakePerk2"]
                ],
                [
                    "name" => "Fake Perk with Lorem Ipsum",
                    "description" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec faucibus porta dui, vel porta leo consectetur vel. Sed a nisl ligula. Donec sed nisi et magna commodo euismod id et dolor. Aliquam sed sapien quis est semper tempus. Curabitur nec lacus sit amet massa sodales accumsan ut eget urna. Vivamus justo felis, convallis et sapien id, dapibus aliquam mauris. Cras sit amet nulla eu odio ultrices congue sed non ipsum. Phasellus ut lacinia arcu, quis volutpat justo. Proin aliquet, dui et euismod cursus, ligula metus fringilla orci, nec mattis sem nunc a dui. Phasellus a velit quis nisi auctor pharetra. Integer lacus libero, consequat congue egestas vel, finibus id leo. Duis velit nulla, scelerisque id justo in, dignissim mollis dui. ",
                    "category" => 'appearance',
                    "excludes" => ["FakePerk2"]
                ],
                [
                    "name" => "Fake Perk in a different category",
                    "description" => "As noted",
                    "category" => 'history',
                    "excludes" => ["FakePerk2"]
                ]
            ],
            "perkCategories" => [
                [
                    "category" => "infection",
                    "label" => "Infection Resistance",
                    "description" => "These perks control the overall rate of how quickly or how slowly transformation will effect you."
                ],
                [
                    "category" => "gender",
                    "label" => "Gender Perks",
                    "description" => "There are many more preference related perks but these are the critical ones controlling your gender preferences."
                ],
                [
                    "category" => "appearance",
                    "label" => "Appearance",
                    "description" => "Following on from gender perks, these perks control how you appear to others."
                ],
                [
                    "category" => "history",
                    "label" => "Historic",
                    "description" => "Finally, these perks effect how you start in this world."
                ]
            ]
        ];
        return json_encode($config);
    }

    public function request(string $request, array $data = []): string
    {
        Log::debug('FakeMuckRequest:' . $request . ', request: ' . json_encode($data));
        $this->populateDatabaseIfRequired();

        $fakerFunction = 'fake_' . $request;
        if (method_exists($this, $fakerFunction)) {
            return $this->$fakerFunction($data);
        }
        throw new Error("FakeMuckRequest - No faker implementation for $request");
    }
}
