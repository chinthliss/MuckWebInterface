<?php

namespace App\Muck;

use Carbon\Carbon;
use Error;
use Illuminate\Support\Facades\Log;
use Database\Seeders\DatabaseSeeder;

class MuckConnectionFaker implements MuckConnection
{

    public function fake_getByDbref(array $data): string
    {
        throw new Error("Not implemented.");
    }

    public function fake_getByPlayerName(array $data): string
    {
        // Format - dbref,creationTimestamp,typeFlag,"name",owningAccount,flagList
        $characterArray = null;
        switch ($data['name']) {
            case 'TestCharacter':
                $characterArray = [1234, Carbon::now()->getTimestamp(), 'p', 'TestCharacter', DatabaseSeeder::$normalUserAccountId, null];
                break;
        }
        if ($characterArray) return join(',', $characterArray);
        return '';
    }

    public function fake_getByApiToken(array $data): string
    {
        throw new Error("Not implemented.");
    }

    public function fake_getCharacters(array $data): string
    {
        throw new Error("Not implemented.");
    }

    public function fake_validateCredentials(array $data): string
    {
        throw new Error("Not implemented.");
    }

    public function request(string $request, array $data = []): string
    {
        Log::debug('FakeMuckRequest:' . $request . ', request: ' . json_encode($data));

        $fakerFunction = 'fake_' . $request;
        if (method_exists($this, $fakerFunction)) {
            return $this->$fakerFunction($data);
        }
        throw new Error("FakeMuckRequest - No faker implementation for $request");
    }
}
