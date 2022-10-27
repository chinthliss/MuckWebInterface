<?php

namespace App\Muck;

use App\User;
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
     * Expected format: 'dbref,creationTimestamp,typeFlag,"name",owningAccount,flagList'
     * FlagList is a : separated list of keywords
     * @param string $response
     * @return MuckDbref
     */
    private function parseDbrefFromResponse(string $response): MuckDbref
    {
        $parts = str_getcsv($response, ',', '"', '\\');
        if (count($parts) != 6)
            throw new InvalidArgumentException("getDbrefFromResponse: Response contains the wrong number of parts: $response");
        list($dbref, $creationTimestamp, $typeFlag, $name, $owningAccount, $flagList) = $parts;

        $flags = explode(':', $flagList);
        $approved = !in_array('unapproved', $flags);
        $staffLevel = 0;
        if (in_array('staff', $flags)) $staffLevel = 1;
        if (in_array('admin', $flags)) $staffLevel = 2;

        return new MuckDbref($dbref, $name, $typeFlag, $creationTimestamp, $owningAccount, $staffLevel, $approved);
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
        //Form of result is \r\n separated lines of dbref,name,level,flags
        foreach (explode(chr(13) . chr(10), $response) as $line) {
            if (!trim($line)) continue;
            $character = $this->parseDbrefFromResponse($line);
            $characters[$character->dbref] = $character;
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
