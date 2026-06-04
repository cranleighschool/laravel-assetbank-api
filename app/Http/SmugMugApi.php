<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Traits\AnnualSmugmugSetup;
use Exception;
use JsonException;
use phpSmug\Client;
use Throwable;

class SmugMugApi
{
    use AnnualSmugmugSetup;

    private string $appName = 'Asset Bank Api';

    public Client $client;

    public string $username;

    /**
     * @throws JsonException
     * @throws Throwable
     */
    public function __construct(string $configJson)
    {
        throw_unless(
            file_exists(base_path($configJson)),
            Exception::class,
            "Cannot find {$configJson} to read SmugMug credentials from."
        );

        $config = json_decode(
            file_get_contents(base_path($configJson)),
            false,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->client = new Client($config->apiKey, [
            'AppName' => $this->appName,
            '_verbosity' => 1,
            'OAuthSecret' => $config->secret,
        ]);
        $this->client->setToken($config->oauth_token, $config->oauth_token_secret);

        $this->username = $this->client->get('!authuser')->User->NickName;
    }

    public function getHouseNodeChildren(string $house): mixed
    {
        $house = ucfirst($house);
        $nodeUri = $this->getHouseFolder($house)->Folder->Uris->Node;

        return $this->client->get($nodeUri.'!children', ['count' => 100]);
    }
}
