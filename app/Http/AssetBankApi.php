<?php

declare(strict_types=1);

namespace App\Http;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class AssetBankApi
{
    public const string API_ROOT = 'https://photos.cranleigh.org/asset-bank/rest/';

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function get(string $endpoint, array $options = []): ?object
    {
        return $this->request('GET', $endpoint, $options);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function request(string $method, string $endpoint, array $options = []): ?object
    {
        $pending = Http::baseUrl(self::API_ROOT)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);

        $response = match (strtoupper($method)) {
            'POST' => $pending->post($endpoint, $options),
            default => $pending->get($endpoint, $options),
        };

        $response->throw();

        return $response->object();
    }
}
