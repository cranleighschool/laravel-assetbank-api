<?php

declare(strict_types=1);

namespace App\Http;

use GuzzleHttp\Client as Guzzle;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class AssetBankApi
{
    /**
     * @var Guzzle
     */
    public Guzzle $api;

    public const string API_ROOT = 'https://photos.cranleigh.org/asset-bank/rest/';

    /**
     * AssetBankController constructor.
     */
    public function __construct()
    {
        $this->api = new Guzzle([
            'base_uri' => self::API_ROOT,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

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
    public function request(string $method, string $endpoint, $options = []): ?object
    {
        $method = strtoupper($method);
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);
        switch ($method) {
            case 'POST':
                $response = $response->post(self::API_ROOT.$endpoint, $options);
                break;
            default:
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->get(self::API_ROOT.$endpoint, $options);
                break;
        }

        $response->throw();

        return $response->object();
    }
}
