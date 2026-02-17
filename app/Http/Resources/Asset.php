<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class Asset
 */
class Asset extends JsonResource
{
    /**
     * @var \Illuminate\Support\Collection
     */
    private $attrs;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        $this->attrs = collect($this->attributes);

        return [
            'url' => $this->url,
            'submitted' => $this->submitted,
            'approved' => $this->approved,
            'parents' => $this->parents,
            'contentUrl' => $this->contentUrl,
            'displayUrl' => $this->displayUrl,
            // 'attributes' => $this->attributes,
            'attrs' => $this->getAttrs(),
        ];
    }

    private function getAttrs(): array
    {
        $attrs = [];
        foreach ($this->attrs as $attr) {
            $attrs[$attr->name] = $this->getAttribute($attr->name);
        }

        return $attrs;
    }

    /**
     * @param  \App\Http\Resources\string  $name
     * @return |null
     */
    private function getAttribute(string $name)
    {
        $filtered = $this->attrs->filter(function ($item) use ($name) {
            if ($item->name === $name) {
                return $item;
            }
        });
        $value = $filtered->first()->value;
        if ($value === '') {
            return null;
        }

        return $value;
    }
}
