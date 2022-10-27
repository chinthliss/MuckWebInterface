<?php

namespace App\Muck;

use Carbon\Carbon;
use Error;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class MuckConnectionHttp implements MuckConnection
{

    private string $salt;
    private Client $client;
    private string $uri;

    public function __construct(string $baseUrl, string $uri, string $salt)
    {
        $this->client = new Client([
            'base_uri' => $baseUrl
        ]);
        $this->salt = $salt;
        $this->uri = $uri;
    }

    private function redactForLog(array $credentials): array
    {
        if (array_key_exists('password', $credentials)) $credentials['password'] = '********';
        return $credentials;
    }

    public function request(string $request, array $data = []): string
    {
        Log::debug('HttpMuckRequest:' . $request . ', request: ' . json_encode($this->redactForLog($data)));
        $data['mwi_request'] = $request;
        $data['mwi_timestamp'] = Carbon::now()->timestamp; //This is to ensure that repeated requests don't match
        $signature = sha1(http_build_query($data) . $this->salt);
        $benchmark = -microtime(true);
        try {
            $result = $this->client->request('POST', $this->uri, [
                'headers' => [
                    'Signature' => $signature
                ],
                'form_params' => $data
            ]);
        } catch (GuzzleException $e) {
            throw new Error("Connection to muck failed - " . $e->getMessage());
        }
        $benchmark += microtime(true);
        $benchmarkText = round($benchmark * 1000.0, 2);
        //getBody() returns a stream, so need to ensure we complete and parse such:
        //The result will also have a trailing \r\n
        $parsedResult = rtrim($result->getBody()->getContents());
        Log::debug("HttpMuckRequest: $request, time taken: {$benchmarkText}ms, response: " . json_encode($parsedResult));
        return $parsedResult;
    }
}
