<?php

namespace App\Muck;

use App\User;
use Carbon\Carbon;
use InvalidArgumentException;

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
        $parts = str_getcsv($response, ',', '"', '\\');
        if (count($parts) < 4)
            throw new InvalidArgumentException("getDbrefFromResponse: Response doesn't contain enough parts (minimum of 4): $response");
        list($dbref, $creationTimestamp, $typeFlag, $name) = $parts;
        // Extended properties
        $properties = [];
        for ($i = 4; $i < count($parts); $i++) {
            list($key, $value) = explode('=', $parts[$i], 2);
            $properties[$key] = $value;
        }
        return new MuckDbref($dbref, $name, $typeFlag, Carbon::createFromTimestamp($creationTimestamp), $properties);
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
}
