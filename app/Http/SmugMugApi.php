<?php

namespace App\Http;

use App\Http\Traits\AnnualSmugmugSetup;
use phpSmug\Client;

/**
 * Class SmugMugApi
 */
class SmugMugApi
{
    use AnnualSmugmugSetup;

    /**
     * @var string
     */
    private $appName = 'Asset Bank Api';

    /**
     * @var \phpSmug\Client
     */
    public $client;

    /**
     * @var string
     */
    public $username;

    /**
     * SmugMugApi constructor.
     *
     * @param  string  $configJson
     *
     * @throws \JsonException
     */
    public function __construct(string $configJson)
    {
        if (! file_exists(base_path($configJson))) {
            throw new \Exception("I can't find ".$configJson.' to read the config from.');
        }

        $config = json_decode(
            file_get_contents(base_path($configJson)),
            false,
            512,
            JSON_THROW_ON_ERROR
        );

        $options = [
            'AppName' => $this->appName,
            '_verbosity' => 1,
            // Reduce verbosity to reduce the amount of data in the response and to make using it easier.
            'OAuthSecret' => $config->secret,
            // You need to pass your OAuthSecret in order to authenticate with OAuth.
        ];

        $this->client = new Client($config->apiKey, $options);
        $this->client->setToken($config->oauth_token, $config->oauth_token_secret);
        $this->setUsername();
    }

    private function setUsername()
    {
        $this->username = $this->client->get('!authuser')->User->NickName;
    }

    public function getHouseNodeChildren(string $house)
    {
        $house = ucfirst($house);
        $nodeUri = $this->getHouseFolder($house)->Folder->Uris->Node;

        return $this->client->get($nodeUri.'!children', [
            'count' => 100,
        ]);
    }
}
