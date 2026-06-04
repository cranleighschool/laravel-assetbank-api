<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Asset;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;
use stdClass;

class AssetBankController extends Controller
{
    private const string API_ROOT = 'https://photos.cranleigh.org/asset-bank/rest/';

    // AssetBank attribute IDs
    private const int ATTR_SUITABLE_FOR_PUBLICATION = 705;
    private const int ATTR_CATEGORIES = 17;
    private const int ATTR_DESCRIPTION = 4;
    private const int ATTR_YEAR_GROUPS = 718;
    private const int ATTR_TITLE = 3;
    private const int ATTR_PHOTOGRAPHER = 706;
    private const int ATTR_RATINGS = 709;
    private const int ATTR_EVENT_NAME = 716;
    private const int ATTR_DATE_ADDED = 8;
    private const int ATTR_MIGRATION_STATUS = 730;

    /** @var SimpleXMLElement|null Set by the legacy get() method for XML-based endpoints */
    protected ?SimpleXMLElement $result = null;

    protected int $count = 0;

    /**
     * @throws Exception
     */
    public function getAssetByID(mixed $id, bool $new = false): ?object
    {
        return $this->newApi('assets/'.$id);
    }

    /**
     * @throws ConnectionException
     * @throws Exception
     */
    public function newApi(string $endpoint, array $options = []): object
    {
        $query = ! empty($options) ? '?'.http_build_query($options) : null;

        try {
            return Http::baseUrl(self::API_ROOT)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->get($endpoint.$query)
                ->throw()
                ->object();
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
     * Fetches a JSON endpoint from the AssetBank API and returns the decoded array.
     *
     * @throws Exception
     */
    public function api(string $endpoint, array $options = []): array
    {
        try {
            return Http::baseUrl(self::API_ROOT)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->get($endpoint, $options)
                ->throw()
                ->json();
        } catch (RequestException $e) {
            abort_if($e->response->status() === 404, 404, $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Fetches an XML endpoint from the AssetBank API, storing results in $this->result.
     *
     * Note: SSL certificate verification is intentionally disabled here for the legacy
     * XML API endpoint, which sits behind a self-signed certificate on the internal network.
     */
    public function get(string $endpoint, array $options = []): void
    {
        $query = ! empty($options) ? '?'.http_build_query($options) : '';

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $xml = file_get_contents(self::API_ROOT.$endpoint.$query, false, $context);
        $this->result = simplexml_load_string($xml ?: '') ?: null;
        $this->count = $this->result ? count($this->result) : 0;

        if (isset($this->result->assetSummary) && is_scalar($this->result->assetSummary)) {
            foreach ($this->result->assetSummary as $asset) {
                $asset->title = $this->get_asset_title($asset);
                unset($asset->displayUrl, $asset->contentUrl);
            }
        }
    }

    public function tagAsMigrated(int $id): mixed
    {
        $request = new Request;
        $request->merge([[
            'id' => self::ATTR_MIGRATION_STATUS,
            'name' => 'Migration Status',
            'value' => '1',
            'label' => 'Migration Status',
        ]]);

        return $this->updateAssetById($id, $request);
    }

    public function updateAssetById(int $id, Request $request): mixed
    {
        $client = Http::baseUrl(self::API_ROOT)
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
    public function relatedImages(int $id): JsonResponse
    {
        $asset = $this->buildAsset($id);
        $asset->relatedAssets = $this->buildRelatedEvents($asset->event_name, (string) $id);

        return response()->json($asset);
    }

    /**
     * @throws Exception
     */
    public function getAssetInfoForWebsite(int $id): JsonResponse
    {
        return response()->json($this->buildAsset($id));
    }

    /**
     * @throws Exception
     */
    private function buildAsset(int $id): Asset
    {
        $response = $this->api('assets/'.$id);
        $asset = new Asset($id);

        $asset->tags[] = 'assetbank';

        $this->setReadableAttributes($asset, $response['attributes']);

        $asset->tags = array_values(array_unique($asset->tags));

        $asset->websiteCriteriaCheck = $this->websiteCriteriaCheck($asset);

        return $asset;
    }

    private function setReadableAttributes(Asset $asset, mixed $attributes): void
    {
        foreach ($attributes as $attribute) {
            match ($attribute['id']) {
                self::ATTR_SUITABLE_FOR_PUBLICATION => abort_if(
                    $attribute['value'] !== 'Yes',
                    403,
                    'Image is not set as suitable for publication'
                ),
                self::ATTR_CATEGORIES => $asset->setCategories($attribute['value']),
                self::ATTR_DESCRIPTION => $asset->setDescription($attribute['value']),
                self::ATTR_YEAR_GROUPS => $attribute['value'] ? $asset->addTag($attribute['value']) : null,
                self::ATTR_TITLE => $asset->setTitle($attribute['value']),
                self::ATTR_PHOTOGRAPHER => $asset->setPhotographer($attribute['value']),
                self::ATTR_RATINGS => $asset->rating = explode(';', trim($attribute['value'])),
                self::ATTR_EVENT_NAME => $asset->setEventName($attribute['value']),
                self::ATTR_DATE_ADDED => $asset->setDateAdded($attribute['value']),
                default => null,
            };
        }
    }

    private function websiteCriteriaCheck(Asset $asset): bool
    {
        if (in_array('Exclude DSignage', $asset->tags)) {
            return false;
        }

        return in_array('Best', $asset->rating) && in_array('Highlights', $asset->rating);
    }

    /**
     * @throws Exception
     */
    public function relatedEvents(mixed $searchTerm, ?string $exclude = null): JsonResponse
    {
        return response()->json($this->buildRelatedEvents($searchTerm, $exclude));
    }

    /**
     * @throws Exception
     */
    private function buildRelatedEvents(mixed $searchTerm, ?string $exclude = null): stdClass
    {
        $notinclude = $exclude !== null
            ? array_map('trim', explode(',', $exclude))
            : [];

        $response = $this->api('asset-search', [
            'attribute_716' => $searchTerm,
            'pageSize' => 50,
        ]);

        if ($notinclude !== []) {
            $response = array_filter($response, fn ($asset) => ! in_array($asset['id'], $notinclude));
        }

        $result = new stdClass;
        $result->assets = array_values(array_map(
            fn ($asset) => $this->buildAsset($asset['id']),
            $response
        ));
        $result->num = count($result->assets);

        return $result;
    }

    public function getAttributes(): JsonResponse
    {
        $this->get('attributes');

        return response()->json($this->result);
    }

    public function get_recent_photos_from_group(mixed $groupID, int $numPhotos = 15): JsonResponse
    {
        $this->get('asset-search', [
            'descriptiveCategoryForm.categoryIds' => $groupID,
            'pageSize' => $numPhotos,
            'attribute_701' => 'Senior',
            'sortAttributeId' => 7,
            'sortDescending' => 'true',
            'orientation' => 1,
            'attribute_709' => 'Best',
        ]);

        return response()->json($this->result);
    }

    /**
     * @throws Exception
     */
    public function listAssetBankCategories(): array
    {
        return $this->newGetCategories();
    }

    /**
     * @throws Exception
     */
    public function newGetCategories(): array
    {
        $categoryTree = $this->api('category-search');
        $cats = [];

        foreach ($categoryTree as $category) {
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

    public function get_categories(): array
    {
        $this->get('category-search');
        $cats = [];

        foreach ($this->result ?? [] as $tlc) {
            if ($tlc->children && is_object($tlc->children)) {
                $cats += $this->loop($tlc->children);
            }

            $cats[(int) $tlc->id] = (string) $tlc->name;
        }

        return $cats;
    }

    public function loop(mixed $object): array
    {
        $output = [];
        foreach ($object->category as $child) {
            $output[(int) $child->id] = (string) $child->name;
            if ($child->children && is_object($child->children)) {
                $output += $this->loop($child->children);
            }
        }

        return $output;
    }
}
