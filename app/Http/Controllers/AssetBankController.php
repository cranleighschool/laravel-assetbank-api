<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Asset;
use Exception;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use stdClass;

/**
 * Class AssetBankController
 */
class AssetBankController extends Controller
{
    /**
     * @var array
     */
    protected $guzzleOpts = [];

    /**
     * @var string
     */
    private $root_url = 'https://photos.cranleigh.org/asset-bank/rest/';

    /**
     * AssetBankController constructor.
     */
    public function __construct()
    {
        $this->guzzle = new Guzzle([
            'base_uri' => $this->root_url,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * @param  bool  $new
     * @return \App\Http\Resources\Asset
     *
     * @throws Exception
     */
    public function getAssetByID($id, $new = false)
    {
        $response = $this->newApi('assets/'.$id);

        return $response;

        return new \App\Http\Resources\Asset($response);
    }

    /**
     * @param  array  $options
     */
    public function newApi($endpoint, $options = []): object
    {
        if (! empty($options)) {
            $query = '?'.http_build_query($options);
        } else {
            $query = null;
        }
        try {
            $response = Http::baseUrl('https://photos.cranleigh.org/asset-bank/rest/')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->get($endpoint.$query)
                ->throw();

            return $response->object();
        } catch (RequestException $exception) {
            if ($exception->getCode() == 403) {
                return response()->json([
                    'error' => 'The server could not authenticate with the Asset Bank API.',
                ], 401);
            }
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * @param  array  $options
     */
    public function get($endpoint, $options = [])
    {
        if (! empty($options)) {
            $query = '?'.http_build_query($options);
        } else {
            $query = '';
        }

        $arrContextOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ];

        $this->query = $query;
        $this->endpoint = $endpoint;
        $xml = file_get_contents($this->root_url.$endpoint.$query, false,
            stream_context_create($arrContextOptions));
        //  $this->result = simplexml_load_file($this->root_url.$endpoint.$query);
        $this->result = simplexml_load_string($xml);
        $this->count = count($this->result);

        if (isset($this->result->assetSummary) && is_scalar($this->result->assetSummary)) {
            foreach ($this->result->assetSummary as $asset) {
                $asset->title = $this->get_asset_title($asset);
                //    $moreDetails = simplexml_load_file($this->root_url."assets/".$asset->id)->url;
                //    $asset->details = $moreDetails;
                //    $asset->image = $this->root_url."assets/".$asset->id."/display";
                //    $asset->contentUrl = $this->root_url."assets/".$asset->id;
                //    $asset->displayUrl = $this->root_url."assets/".$asset->id;
                unset($asset->displayUrl);
                unset($asset->contentUrl);
                // unset($asset->fullAssetUrl);
                // unset($asset->displayAttributes);
            }
        }
    }

    public function tagAsMigrated(int $id)
    {
        $request = new Request;
        $request->merge([
            [
                'id' => 730,
                'name' => 'Migration Status',
                'value' => '1',
                'label' => 'Migration Status',
            ],
        ]
        );

        return $this->updateAssetById($id, $request);
    }

    public function updateAssetById(int $id, Request $request)
    {
        $client = Http::baseUrl('https://photos.cranleigh.org/asset-bank/rest/')
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]);
        $attributes = $this->newApi('assets/'.$id)->attributes;
        $mergedArray = array_merge($attributes, $request->all());

        return $client->put('assets/'.$id, [
            'submitted' => true,
            'attributes' => $mergedArray,
        ])->throw()->object();
    }

    /**
     * @throws Exception
     */
    public function relatedImages($id): JsonResponse
    {
        $asset = $this->getAssetInfoForWebsite($id, true);

        $asset->relatedAssets = $this->relatedEvents($asset->event_name, $id, true);

        return response()->json($asset);
    }

    /**
     * @param  bool  $raw
     * @return Asset|JsonResponse
     *
     * @throws Exception
     */
    public function getAssetInfoForWebsite($id, $raw = false)
    {
        $response = $this->api('assets/'.$id);
        $asset = new Asset($id);

        $asset->tags[] = 'assetbank';

        $this->setReadableAttributes($asset, $response['attributes']);

        $asset->tags = $this->simplify_tag_list($asset->tags);

        $asset->websiteCriteriaCheck = $this->websiteCriteriaCheck($asset);

        // Uncomment the line below to help with debugging
        // $asset->attributes = $response['attributes'];

        if ($raw === true) {
            return $asset;
        } else {
            return response()->json($asset);
        }
    }

    /**
     * @param  array  $options
     * @return mixed
     *
     * @throws Exception
     */
    public function api($endpoint, $options = [])
    {
        if (! empty($options)) {
            $query = '?'.http_build_query($options);
        } else {
            $query = null;
        }

        try {
            $response = $this->guzzle->get($endpoint.$query, $this->guzzleOpts());
        } catch (BadResponseException $e) {
            $resp = $e->getResponse();

            abort_if($resp->getStatusCode() == 404, 404, $e->getMessage());
            throw new Exception($e->getMessage());
        }

        return json_decode($response->getBody(), true);
    }

    /**
     * @return array
     */
    protected function guzzleOpts(?array $vars = null)
    {
        if ($vars !== null) {
            foreach ($vars as $key => $var) {
                $this->guzzleOpts[$key] = $var;
            }
        }

        return $this->guzzleOpts;
    }

    private function setReadableAttributes(Asset $asset, $attributes): void
    {
        foreach ($attributes as $attribute) {
            // Is this asset marked as Suitable for Publication
            if ($attribute['id'] == 705) {
                abort_if($attribute['value'] !== 'Yes', 403, 'Image is not set as suitable for publication');
            }

            // Get Categories
            if ($attribute['id'] == 17) {
                $asset->setCategories($attribute['value']);
            }

            // Get Description
            if ($attribute['id'] == 4) {
                $asset->setDescription($attribute['value']);
            }

            // Get the Year Groups
            if ($attribute['id'] == 718 && $attribute['value']) {
                $asset->addTag($attribute['value']);
            }

            // Get Title
            if ($attribute['id'] == 3) {
                $asset->setTitle($attribute['value']);
            }

            // Get Photographer's Name
            if ($attribute['id'] == 706) {
                $asset->setPhotographer($attribute['value']);
            }

            // Get Ratings as array
            if ($attribute['id'] == 709) {
                $asset->rating = explode(';', trim($attribute['value']));
            }

            // Get Event Name
            if ($attribute['id'] == 716) {
                $asset->setEventName($attribute['value']);
            }

            // Get DateTime of date added
            if ($attribute['id'] == 8) {
                $asset->setDateAdded($attribute['value']);
            }
        } // End foreach
    }

    /**
     * @return array
     */
    private function simplify_tag_list($tags)
    {
        return array_values(array_unique($tags));
    }

    private function websiteCriteriaCheck(Asset $asset): bool
    {
        if (in_array('Exclude DSignage', $asset->tags)) {
            return false;
        }

        if (in_array('Best', $asset->rating) && in_array('Highlights', $asset->rating)) {
            return true;
        }

        return false;
    }

    /**
     * @param  bool  $raw
     * @return JsonResponse|stdClass
     *
     * @throws Exception
     */
    public function relatedEvents($searchTerm, ?string $exclude = null, $raw = false)
    {
        if ($exclude !== null) {
            $notinclude = array_map(function ($v) {
                return trim($v);
            }, explode(',', $exclude));
        }

        $args = [
            'attribute_716' => $searchTerm,
            'pageSize' => 50,
        ];

        $response = $this->api('asset-search', $args);

        $result = new stdClass;

        $result->assets = [];

        if (! empty($notinclude)) {
            foreach ($response as $key => $asset) {
                if (in_array($asset['id'], $notinclude)) {
                    unset($response[$key]);
                }
            }
        }
        foreach ($response as $asset) {
            $result->assets[] = $this->getAssetInfoForWebsite($asset['id'], true);
        }
        $result->num = count($response);

        if ($raw === true) {
            return $result;
        } else {
            return response()->json($result);
        }
    }

    public function getAttributes(): JsonResponse
    {
        $xml = $this->get('attributes');

        return response()->json($this->result);
    }

    /**
     * get_recent_photos_from_group function.
     *
     *
     * @param  mixed  $groupID
     * @param  int  $numPhotos  (default: 15)
     * @return void
     */
    public function get_recent_photos_from_group($groupID, $numPhotos = 15)
    {
        $xml = $this->get(
            'asset-search',
            [
                'descriptiveCategoryForm.categoryIds' => $groupID,
                'pageSize' => $numPhotos,
                'attribute_701' => 'Senior',
                'sortAttributeId' => 7,
                'sortDescending' => 'true',
                'orientation' => 1,
                'attribute_709' => 'Best',
                //    "includeImplicitCategoryMembers"=>"true"
            ]
        );

        //  var_dump($this->result);
        return response()->json($this->result);
    }

    /**
     * @throws Exception
     */
    public function listAssetBankCategories(): JsonResponse
    {
        return $this->newGetCategories();
        $categories = $this->get_categories();
        $data = ['count' => count($categories), 'categories' => $categories];

        return response()->json($data);
    }

    /**
     * @throws Exception
     */
    public function newGetCategories(): array
    {
        $categoryTree = $this->api('category-search');
        $cats = [];

        foreach ($categoryTree as $category) {
            // don't show anything in the branding category
            if ($category['id'] == '192') {
                continue;
            }
            $cats[$category['id']] = $category['name'];
            if ($category['children']) {
                foreach ($category['children'] as $child) {
                    $cats[$child['id']] = $child['name'];
                }
            }
        }

        return $cats;
    }

    /**
     * @return array|void
     */
    public function get_categories(): array
    {
        $this->get('category-search');
        $cats = [];

        foreach ($this->result as $tlc) {
            if ($tlc->children && is_object($tlc->children)) {
                $children = $this->loop($tlc->children);
                $cats += $children;
            } else {
            }

            $cats[(int) $tlc->id] = (string) $tlc->name; // . " ".$more;
        }

        return $cats; // $this->result;
    }

    /**
     * loop function.
     *
     * This function is used to iterate through the children of each category. Used in $this->get_categories()
     *
     *
     * @param  mixed  $object
     */
    public function loop($object): array
    {
        $output = [];
        $loop = [];
        foreach ($object->category as $child) {
            $output[(int) $child->id] = (string) $child->name;
            if ($child->children && is_object($child->children)) {
                $children = $this->loop($child->children);
                $output += $children;
            }
        }

        return $output;
    }
}
