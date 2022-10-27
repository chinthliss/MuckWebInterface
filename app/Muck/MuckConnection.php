<?php

namespace App\Muck;

interface MuckConnection
{
    /**
     * Sends a request to the muck.
     * Data send to the muck will be json encoded.
     * The response will be a plain text string, it's up to calling code to decode if required.
     * @param string $request
     * @param array $data
     * @return string
     */
    public function request(string $request, array $data = []): string;
}
