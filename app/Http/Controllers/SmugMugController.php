<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use ErrorException;
use App\Http\SmugMugApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SmugMugController extends Controller
{
    /**
     * SmugMugController constructor.
     */
    public function __construct(Request $request)
    {
        $smugNickname = $request->get('username');
        if ($smugNickname === null) {
            $smugNickname = 'cranleigh';
        }

        $this->smugmug = (new SmugMugApi('smug_'.$smugNickname.'.json'));
    }

    public function getHouseAlbumsOrFolders(string $house)
    {
        return Cache::remember('smug_house_children_nodes_'.$house, now()->addMinutes(15), function () use ($house) {
            $house = ucfirst($house); // Sanitize, just in case

            $houseNodeChildren = $this->smugmug->getHouseNodeChildren($house);
            $houseFolder = $this->smugmug->getHouseFolder($house);
            $nodes = [];
            try {
                foreach ($houseNodeChildren->Node as $node) {
                    $nodes[] = [
                        'type' => $node->Type,
                        'title' => $node->Name,
                        'uri' => $node->WebUri,
                        'thumb' => $this->smugmug->client->get($node->Uris->NodeCoverImage)->Image->ThumbnailUrl,
                    ];
                }
            } catch (ErrorException $exception) {
                Log::error($exception->getMessage());
            }
            $houseFolder->children = $nodes;

            $response = [
                'name' => $houseFolder->Folder->Name,
                'uri' => $houseFolder->Folder->WebUri,
                'children' => $nodes,
            ];

            return response()->json($response);
        });
    }
}
