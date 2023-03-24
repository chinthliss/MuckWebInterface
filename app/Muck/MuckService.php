<?php

namespace App\Muck;

use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use App\Helpers\Ansi;

class MuckService
{
    /**
     * @param MuckConnection $connection
     */
    public function __construct(
        private MuckConnection $connection
    )
    {

    }

    /**
     * Expected format: 'dbref,creationTimestamp,typeFlag,"name","property1=value1",.."propertyN=valueN"'
     * @param string $response
     * @return MuckDbref
     */
    private function parseDbrefFromResponse(string $response): MuckDbref
    {
        Log::debug("getDbrefFromResponse: Parsing:  $response");
        $parts = str_getcsv($response, ',', '"', '\\');
        if (count($parts) < 5)
            throw new InvalidArgumentException("getDbrefFromResponse: Response doesn't contain enough parts (minimum of 5): $response");
        list($dbref, $creationTimestamp, $lastUsedTimestamp, $typeFlag, $name) = $parts;
        // Extended properties
        $properties = [];
        for ($i = 5; $i < count($parts); $i++) {
            list($key, $value) = explode('=', $parts[$i], 2);
            $properties[$key] = $value;
        }
        $result = new MuckDbref($dbref, $name, $typeFlag,
            Carbon::createFromTimestamp($creationTimestamp), Carbon::createFromTimestamp($lastUsedTimestamp),
            $properties);
        Log::debug("getDbrefFromResponse: Parsed as: $result");
        return $result;
    }

    /**
     * Ideally this should be called from MuckObjectService to allow caching
     * @param int $dbref
     * @return MuckDbref|null
     */
    public function getByDbref(int $dbref): ?MuckDbref
    {
        $response = $this->connection->request('getByDbref', ['dbref' => $dbref]);
        if (!$response) return null;
        return $this->parseDbrefFromResponse($response);
    }

    /**
     * Ideally this should be called from MuckObjectService to allow caching
     * @param string $playerName
     * @return MuckDbref|null
     */
    public function getByPlayerName(string $playerName): ?MuckDbref
    {
        $response = $this->connection->request('getByPlayerName', ['name' => $playerName]);
        if (!$response) return null;
        return $this->parseDbrefFromResponse($response);
    }

    /**
     * Ideally this should be called from MuckObjectService to allow caching
     * @param string $apiToken
     * @return MuckDbref|null
     */
    public function getByApiToken(string $apiToken): ?MuckDbref
    {
        $response = $this->connection->request('getByApiToken', ['token' => $apiToken]);
        if (!$response) return null;
        return $this->parseDbrefFromResponse($response);
    }

    /**
     * Ideally this should be called from MuckObjectService to allow caching
     * @param User $user
     * @return MuckDbref[]
     */
    public function getCharactersOf(User $user): array
    {
        $characters = [];
        $response = $this->connection->request('getCharacters', ['aid' => $user->id()]);
        //Form of result is characters separated by \r\n.
        foreach (explode(chr(13) . chr(10), $response) as $line) {
            if (!trim($line)) continue;
            $character = $this->parseDbrefFromResponse($line);
            $characters[] = $character;
        }
        return $characters;
    }

    /**
     * @param string $name
     * @return User[]
     */
    public function findAccountsByCharacterName(string $name): array
    {
        $accounts = [];
        $response = $this->connection->request('findAccountsByCharacterName', ['name' => $name]);
        //Form of result is account IDs separated by \r\n
        foreach (explode(chr(13) . chr(10), $response) as $line) {
            if (!trim($line)) continue;
            $accounts[] = User::find($line);
        }
        return $accounts;

    }

    /**
     * Given a character and credentials, asks the muck to verify them (via password)
     * @param MuckDbref $character
     * @param array $credentials
     * @return bool
     */
    public function validateCredentials(MuckDbref $character, array $credentials): bool
    {
        if (!array_key_exists('password', $credentials)) return false;
        return $this->connection->request('validateCredentials', [
            'dbref' => $character->dbref,
            'password' => $credentials['password']
        ]);
    }

    /**
     * Gets a string containing a list of any problems with the specified name being used on the MUCK.
     * Returns an empty string if everything is okay
     * @param string $name
     * @return string
     */
    public function findProblemsWithCharacterName(string $name): string
    {
        return $this->connection->request('findProblemsWithCharacterName', [
            'name' => $name
        ]);
    }


    /**
     * Gets a string containing a list of any problems with the specified password being used on the MUCK.
     * Returns an empty string if everything is okay
     * @param string $password
     * @return string
     */
    public function findProblemsWithCharacterPassword(string $password): string
    {
        return $this->connection->request('findProblemsWithCharacterPassword', [
            'password' => $password
        ]);
    }

    /**
     * @param User $user
     * @param MuckDbref $character
     * @param string $password
     * @return bool
     */
    public function changeCharacterPassword(User $user, MuckDbref $character, string $password): bool
    {
        $response = $this->connection->request('changeCharacterPassword', [
            'aid' => $user->id(),
            'dbref' => $character->dbref,
            'password' => $password
        ]);
        return ($response === 'OK');
    }

    public function getCharacterSlotStateFor(User $user): array
    {
        $response = $this->connection->request('getCharacterSlotState', [
            'aid' => $user->id()
        ]);
        $state = explode(',', $response, 2);
        return [
            'count' => $state[0],
            'cost' => $state[1]
        ];
    }

    public function buyCharacterSlot(User $user): array
    {
        $response = $this->connection->request('buyCharacterSlot', [
            'aid' => $user->id()
        ]);
        $result = explode(',', $response, 3);

        // On error we get ['ERROR',message]
        if ($result[0] == 'ERROR') {
            return [
                "result" => "ERROR",
                "error" => $result[1]
            ];
        }

        // On success we get ['OK',characterSlotCount,characterSlotCost]
        return [
            "result" => "OK",
            "characterSlotCount" => $result[1],
            "characterSlotCost" => $result[2]
        ];
    }

    /**
     * Returns [result:'OK', character, initialPassword] or [result:'ERROR', error]
     * @param User $user
     * @param string $name
     * @return array
     */
    public function createCharacterFor(User $user, string $name): array
    {
        $response = $this->connection->request('createCharacter', [
            'aid' => $user->id(),
            'name' => $name
        ]);
        $result = explode('|', $response, 3);

        // On error we get ['ERROR',message]
        if ($result[0] == 'ERROR') {
            return [
                "result" => "ERROR",
                "error" => $result[1]
            ];
        }

        //On success we get ['OK',character,initialPassword]
        return [
            "result" => "OK",
            "character" => $this->getByDbref($result[1]),
            "initialPassword" => $response[2]
        ];
    }

    public function getCharacterInitialSetupConfigurationFor(User $user): array
    {
        $response = $this->connection->request('getCharacterInitialSetupConfiguration', ['aid' => $user->id()]);
        $config = json_decode($response, true);
        foreach (['factions', 'perks', 'flaws', 'perkCategories'] as $section) {
            foreach ($config[$section] as &$item) {
                if (array_key_exists('description', $item))
                    $item['description'] = Ansi::unparsedToHtml($item['description']);
            }
        }
        return $config;

    }

    /**
     * Character Request should contain [dbref, gender, birthday, faction, perks and flaws]
     * Returns [result:'OK'] or [result:'ERROR', messages:[]]
     * @param array $characterRequest
     * @return array
     */
    public function finalizeCharacter(array $characterRequest): array
    {
        $response = $this->connection->request('finalizeCharacter', $characterRequest);

        // On success we get the string 'OK'
        if ($response === 'OK') return ["result" => "OK"];

        // On error we get errors separated by newlines
        return [
            "result" => "ERROR",
            "messages" => $response ? explode(chr(13) . chr(10), $response) : ['A server issue occurred']
        ];
    }
}
