<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Class Asset
 */
class Asset
{
    protected const string ASSET_BANK_ROOT = 'https://photos.cranleigh.org/asset-bank/';

    public int $asset_id;

    public string $title;

    public string $description;

    public string $event_name;

    public string $dateAdded;

    public string $photographer;

    public string $photo;

    public string $websiteQuality;

    public string $heroQuality;

    public string $assetBankUri;

    /** @var array<string> */
    public array $tags = [];

    /** @var array<string> */
    public array $rating = [];

    public bool $websiteCriteriaCheck = false;

    /** @var array<string> */
    protected array $categories = [];

    public function __construct(int $asset_id)
    {
        $this->asset_id = $asset_id;

        $this->setUris();
    }

    public function setTitle(string $title): void
    {
        if ($title !== '') {
            $this->title = trim($title);
        }
    }

    public function setDescription(string $description): void
    {
        if ($description !== '') {
            $this->description = trim($description);
        }
    }

    public function setEventName(string $event_name): void
    {
        if ($event_name !== '') {
            $this->event_name = trim($event_name);
        }
    }

    public function setPhotographer(string $photographer): void
    {
        if ($photographer !== '') {
            $this->photographer = trim($photographer);
        }
    }

    protected function explodeCategories(string $value): void
    {
        foreach (explode('/', $value) as $single_category) {
            $this->categories[] = $single_category;
        }
    }

    public function setCategories(string $value): void
    {
        if (str_contains($value, ';')) {
            foreach (explode(';', $value) as $cat) {
                $this->explodeCategories($cat);
            }
        } else {
            $this->explodeCategories($value);
        }

        foreach ($this->categories as $category) {
            if (str_contains($category, ';')) {
                foreach (explode(';', $category) as $cat) {
                    $this->addTag($this->sanitizeCategory($cat));
                }

                continue;
            }
            $this->addTag($this->sanitizeCategory($category));
        }
    }

    private function sanitizeCategory(string $category): string
    {
        return trim(str_replace(['(Boys)', '(Girls)'], '', $category));
    }

    public function setDateAdded(string $date): void
    {
        if ($date !== '') {
            $this->dateAdded = trim($date);
        }
    }

    public function addTag(string $tag): void
    {
        $this->tags[] = trim($tag);
    }

    private function setUris(): void
    {
        $this->assetBankUri = self::ASSET_BANK_ROOT.'action/viewAsset?id='.$this->asset_id;
        $this->photo = route('resizedImage', [$this->asset_id]);
        $this->websiteQuality = $this->getPhotoUri(800);
        $this->heroQuality = $this->getPhotoUri(2880);
    }

    private function getPhotoUri(int $size): string
    {
        return route('resizedImage', [$this->asset_id, $size]);
    }
}
