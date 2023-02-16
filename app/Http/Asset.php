<?php

namespace App\Http;

/**
 * Class Asset
 */
class Asset
{
    /**
     * @var int
     */
    public $asset_id;

    /**
     * @var
     */
    public $title;

    /**
     * @var
     */
    public $description;

    /**
     * @var
     */
    public $event_name;

    /**
     * @var
     */
    public $dateAdded;

    /**
     * @var
     */
    public $photographer;

    /**
     * @var
     */
    public $photo;

    /**
     * @var
     */
    public $websiteQuality;

    /**
     * @var
     */
    public $heroQuality;

    /**
     * @var
     */
    public $assetBankUri;

    /**
     * @var array
     */
    public $tags = [];

    /**
     * @var array
     */
    public $rating = [];

    /**
     * @var bool
     */
    public $websiteCriteriaCheck = false;

    /**
     * @var int
     */
    private $id;

    /**
     * @var array
     */
    protected $categories = [];

    /**
     * @var string
     */
    protected $assetBankRoot = 'https://photos.cranleigh.org/asset-bank/';

    /**
     * Asset constructor.
     *
     * @param  int  $asset_id
     */
    public function __construct(int $asset_id)
    {
        $this->asset_id = $asset_id;
        $this->id = $this->asset_id;

        $this->setUris();
    }

    /**
     * @param  string  $title
     */
    public function setTitle(string $title): void
    {
        if ($title) {
            $this->title = trim($title);
        }
    }

    /**
     * @param  string  $description
     */
    public function setDescription(string $description): void
    {
        if ($description) {
            $this->description = trim($description);
        }
    }

    /**
     * @param  string  $event_name
     */
    public function setEventName(string $event_name): void
    {
        if ($event_name) {
            $this->event_name = trim($event_name);
        }
    }

    /**
     * @param  string  $photographer
     */
    public function setPhotographer(string $photographer): void
    {
        if ($photographer) {
            $this->photographer = trim($photographer);
        }
    }

    /**
     * @param  string  $value
     */
    protected function explodeCategories(string $value): void
    {
        $cats = explode('/', $value);
        foreach ($cats as $single_category) {
            array_push($this->categories, $single_category);
        }
    }

    /**
     * @param  string  $value
     */
    public function setCategories(string $value): void
    {
        $categories = [];
        // If more than one category
        if (strpos($value, ';')) {
            $allcats = explode(';', $value);
            foreach ($allcats as $cat) {
                $this->explodeCategories($value);
            }
        } else {
            $this->explodeCategories($value);
        }

        foreach ($this->categories as $category) {
            // Only import tags, if they are clear - no funny business
            // if (preg_match('/^[a-zA-Z0-9() .\-]+$/i', $category)) {

            /**
             * Edit: Remove the `if preg_match` statement from this, so that it picks up everything as a Tag.
             * We were finding that sometimes if multiple tags were added the correct departmental tag wasn't pulling through
             * as it was on the same line as a semiconlon
             */
            if (strpos($category, ';')) {
                $split = explode(';', $category);
                foreach ($split as $cat) {
                    $this->addTag($this->sanitizeCategory($cat));
                }
                continue;
            }
            $this->addTag($this->sanitizeCategory($category));
            // }
        }
    }

    /**
     * @param  string  $category
     * @return string
     */
    private function sanitizeCategory(string $category): string
    {
        $category = trim($category);
        $category = str_replace('(Boys)', '', $category);
        $category = str_replace('(Girls)', '', $category);

        return trim($category);
    }

    /**
     * @param  string  $date
     */
    public function setDateAdded(string $date): void
    {
        if ($date) {
            $this->dateAdded = trim($date);
        }
    }

    /**
     * @param  string  $tag
     */
    public function addTag(string $tag): void
    {
        array_push($this->tags, trim($tag));
    }

    private function setUris(): void
    {
        $this->assetBankUri = $this->assetBankRoot.'action/viewAsset?id='.$this->id;
        $this->photo = route('resizedImage', [$this->id]);
        $this->websiteQuality = $this->getPhotoUri(800);
        $this->heroQuality = $this->getPhotoUri(2880);
    }

    /**
     * @param  int  $size
     * @return string
     */
    private function getPhotoUri(int $size): string
    {
        return route('resizedImage', [$this->id, $size]);
    }
}
