<?php

namespace App\Muck;

use Error;
use Illuminate\Support\Facades\Log;

class MuckConnectionFaker implements MuckConnection
{

    public function request(string $request, array $data = []): string
    {
        Log::debug('FakeMuckRequest:' . $request . ', request: ' . json_encode($data));

        // TODO: Implement request() method.

        throw new Error("FakeMuckRequest - No faker implementation for $request");
    }
}
