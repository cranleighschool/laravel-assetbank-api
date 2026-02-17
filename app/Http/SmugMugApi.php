<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Traits\AnnualSmugmugSetup;
use Exception;
use JsonException;
use phpSmug\Client;
use Throwable;

/**
 * Class SmugMugApi
 */
class SmugMugApi
{
    use AnnualSmugmugSetup;

    /**
     * @var string
     */
    private string $appName = 'Asset Bank Api';

    /**
     * @var Client
     */
    public Client $client;

    /**
     * @var string
     */
    public $username;

    /**
     * SmugMugApi constructor.
     *
     *
     * @throws JsonException
     * @throws Throwable
     */
    public function __construct(string $configJson)
    {
        throw_unless(file_exists(base_path($configJson)), Exception::class, "I can't find ".$configJson.' to read the config from.');

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

    private function setUsername(): void
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
