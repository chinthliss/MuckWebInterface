<?php

namespace App\Muck;

use App\Avatar\AvatarGradient;
use App\Avatar\AvatarItem;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class MuckService
{
    /**
     * @param MuckConnection $connection
     */
    public function __construct(
        private readonly MuckConnection $connection
    )
    {

    }

    /**
     * Expected format: 'dbref,creationTimestamp,lastUsedTimeStamp,typeFlag,"name","property1=value1",.."propertyN=valueN"'
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
            if (!$parts[$i]) continue; // Might be an empty string chunk if there's no properties
            if (str_contains($parts[$i], '=')) {
                list($key, $value) = explode('=', $parts[$i], 2);
            } else {
                $key = $parts[$i];
                $value = 1;
            }
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
        //Form of result is account IDs separated by commas
        foreach (explode(',', $response) as $line) {
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

    /**
     * Returns an array of [characterSlotCount, characterSlotCost]
     * @param User $user
     * @return array
     */
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

    /**
     * Returns an array of [result:'OK', characterSlotCount:int, characterSlotCost:int] on success.
     * Returns an array of [result:'ERROR', error:string] on failure.
     * @param User $user
     * @return array
     */
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
            "initialPassword" => $result[2]
        ];
    }

    /**
     * Returns an array with factions, perks, flaws and perkCategories
     * @param User $user
     * @return array
     */
    public function getCharacterInitialSetupConfigurationFor(User $user): array
    {
        $response = $this->connection->request('getCharacterInitialSetupConfiguration', ['aid' => $user->id()]);
        return json_decode($response, true);
    }

    /**
     * Takes the array [dbref, gender, birthday, faction, perks? and flaws?]
     * Returns [result:'OK'] or [result:'ERROR', messages:[]]
     * @param array $characterRequest
     * @return array
     */
    public function finalizeCharacter(array $characterRequest): array
    {
        $response = $this->connection->request('finalizeCharacter', ['characterData' => json_encode($characterRequest)]);

        // On success we get the string 'OK'
        if ($response === 'OK') return ["result" => "OK"];

        // On error we get errors separated by newlines
        return [
            "result" => "ERROR",
            "messages" => $response ? explode(chr(13) . chr(10), $response) : ['A server issue occurred']
        ];
    }

    /**
     * Lets the muck react to a notification sent from the web-side of things.
     * @param User $user
     * @param MuckDbref|null $character
     * @param string $message
     * @return int Number of notifications sent muck side
     */
    public function externalNotificationSent(User $user, ?MuckDbref $character, string $message): int
    {
        $count = $this->connection->request('externalNotificationSent',
            ['aid' => $user->id(), 'character' => $character?->dbref, 'message' => $message]);
        return (int)$count;
    }

    /**
     * Gets a single-use auth token from the muck, to allow someone to use it connecting to the websocket
     * @param User|null $user
     * @param MuckDbref|null $character
     * @return string The issued websocket authentication token
     */
    public function getWebsocketAuthTokenFor(?User $user, ?MuckDbref $character): string
    {
        $data = [];
        if ($user) $data['aid'] = $user->id();
        if ($character) $data['dbref'] = $character->dbref;
        return $this->connection->request('getWebsocketAuthTokenFor', $data);
    }

    #region Payment related

    /**
     * Requests a conversion quote from the muck. Returns null if amount isn't acceptable.
     * @param int $accountId
     * @param float $usdAmount
     * @return int|null
     */
    public function usdToAccountCurrencyFor(int $accountId, float $usdAmount): ?int
    {
        $amount = $this->connection->request('usdToAccountCurrency',
            ['aid' => $accountId, 'usd' => $usdAmount]);
        return (int)$amount;
    }

    /**
     * Asks the muck to handle account currency purchases. Allows for bonuses / monthly contributions / etc.
     * @param int $accountId
     * @param float $usdAmount
     * @param int $accountCurrency
     * @param ?string $subscriptionId
     * @return int accountCurrencyRewarded
     */
    public function fulfillAccountCurrencyPurchaseFor(int $accountId, float $usdAmount, int $accountCurrency, ?string $subscriptionId): int
    {
        $amount = $this->connection->request('fulfillAccountCurrencyPurchaseFor', [
            'aid' => $accountId,
            'usd' => $usdAmount,
            'accountCurrency' => $accountCurrency,
            'subscriptionId' => $subscriptionId
        ]);
        return (int)$amount;
    }

    /**
     * @param int $accountId
     * @param int $accountCurrency
     * @return int AmountRewarded
     */
    public function fulfillPatreonSupportFor(int $accountId, int $accountCurrency): int
    {
        $amount = $this->connection->request('fulfillPatreonSupportFor', [
            'aid' => $accountId,
            'accountCurrency' => $accountCurrency
        ]);
        return (int)$amount;
    }

    /**
     * @param int $accountId
     * @param float $usdAmount
     * @param int $accountCurrency
     * @param string $itemCode
     * @return int accountCurrencyRewarded
     */
    public function fulfillRewardedItemFor(int $accountId, float $usdAmount, int $accountCurrency, string $itemCode): int
    {
        $amount = $this->connection->request('fulfillRewardedItemFor', [
            'account' => $accountId,
            'usdAmount' => $usdAmount,
            'accountCurrency' => $accountCurrency,
            'itemCode' => $itemCode
        ]);
        return (int)$amount;
    }

    #endregion Payment related

    #region Avatar related

    /**
     * Fetches an array of each avatar doll and what infections use it
     * @return array<string, array<string>>
     */
    public function avatarDollUsage(): array
    {
        return json_decode($this->connection->request('avatarDollUsage'), true);
    }

    /**
     * Fetch the avatar string the muck uses to represent a character
     * @param MuckDbref $character
     * @return string
     */
    public function getAvatarInstanceStringFor(MuckDbref $character): string
    {
        return $this->connection->request('getAvatarInstanceStringFor', [
            'character' => $character->dbref
        ]);
    }

    /**
     * Fetches owned/available gradients/items for a character
     * This requires an array of [itemId:itemRequirementString] to pass to the muck
     * It returns an array of [items: [itemId: status], gradients: [ownedGradient..]]
     *   With status being either 1 for met requirements, 2 for owned and 3 for both
     * @param MuckDbref $character
     * @param array<string, string> $itemRequirements
     * @return array
     */
    public function getAvatarOptionsFor(MuckDbref $character, array $itemRequirements): array
    {
        return json_decode($this->connection->request('getAvatarOptionsFor', [
            'character' => $character->dbref, 'items' => json_encode($itemRequirements)
        ]), true);
    }

    /**
     * Passes the present avatar customizations to the muck to save to the character
     * @param MuckDbref $character
     * @param array $colors
     * @param array $items
     * @return void
     */
    public function saveAvatarCustomizations(MuckDbref $character, array $colors, array $items): void
    {
        $this->connection->request('saveAvatarCustomizations', [
            'character' => $character->dbref,
            'colors' => json_encode($colors),
            'items' => json_encode($items)
        ]);
    }

    /**
     * Pass a request to buy a gradient to the muck so that it can handle it
     * @param MuckDbref $character
     * @param AvatarGradient $gradient
     * @param string $slot
     * @return string
     */
    public function buyAvatarGradient(MuckDbref $character, AvatarGradient $gradient, string $slot): string
    {
        return $this->connection->request('buyAvatarGradient', [
            'character' => $character->dbref,
            'gradient' => $gradient->name,
            'slot' => $slot,
            'owner' => $gradient->owner?->id()
        ]);
    }

    /**
     * Pass a request to buy an item to the muck so that it can handle it
     * @param MuckDbref $character
     * @param AvatarItem $item
     * @return string
     */
    public function buyAvatarItem(MuckDbref $character, AvatarItem $item): string
    {
        return $this->connection->request('buyAvatarItem', [
            'character' => $character->dbref,
            'itemId' => $item->id,
            'itemName' => $item->name,
            'itemCost' => $item->cost,
            'owner' => $item->owner?->id()
        ]);
    }
    #endregion Avatar related

}
