<?php

namespace App\Http;

use GuzzleHttp\Client as Guzzle;
use Illuminate\Support\Facades\Http;

class AssetBankApi
{
    /**
     * @var \GuzzleHttp\Client
     */
    public $api;

    public const API_ROOT = 'https://photos.cranleigh.org/asset-bank/rest/';

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

    public function get(string $endpoint, array $options = [])
    {
        return $this->request('GET', $endpoint, $options);
    }

    /**
     * @param  array  $options
     * @return object
     */
    public function request(string $method, string $endpoint, $options = [])
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
