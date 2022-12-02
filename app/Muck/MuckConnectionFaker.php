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
        $this->muckDatabase[] = new MuckDbref(1234, 'TestCharacter', 'p', Carbon::now(), [
            'accountId' => strval(DatabaseSeeder::$normalUserAccountId),
            'level' => '10',
            'approved' => '1'
        ]);
        $this->muckDatabase[] = new MuckDbref(1235, 'TestAltCharacter', 'p', Carbon::now(), [
            'accountId' => strval(DatabaseSeeder::$normalUserAccountId),
            'level' => '2',
            'approved' => '1'
        ]);
        $this->muckDatabase[] = new MuckDbref(1235, 'unapprovedCharacter', 'p', Carbon::now(), [
            'accountId' => strval(DatabaseSeeder::$normalUserAccountId),
            'level' => '1'
        ]);

    }

    // Converts a dbref into the string the muck would return, in order to test parsing of such.
    private function dbrefToMuckResponse(MuckDbref $dbref): string
    {
        // Format - dbref,creationTimestamp,typeFlag,"name", .. otherProperties
        $piecesArray = [$dbref->dbref, $dbref->createdTimestamp->getTimestamp(), $dbref->typeFlag, $dbref->name];
        if ($dbref->accountId()) $piecesArray[] = "\"accountId=" . $dbref->accountId() . "\"";
        if ($dbref->level()) $piecesArray[] = "\"level=" . $dbref->level() . "\"";
        if ($dbref->approved()) $piecesArray[] = "\"approved=1\"";
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

    public function fake_validateCredentials(array $data): string
    {
        // All passwords are 'muckpassword' during faking.
        $password = $data['password'];
        return ($password && $password == 'muckpassword');
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
