<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Asset extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Build a name→value lookup once rather than scanning the collection per attribute
        $attrMap = collect($this->attributes)->keyBy('name')->map->value->toArray();

        return [
            'url' => $this->url,
            'submitted' => $this->submitted,
            'approved' => $this->approved,
            'parents' => $this->parents,
            'contentUrl' => $this->contentUrl,
            'displayUrl' => $this->displayUrl,
            'attrs' => array_map(
                fn (mixed $v) => $v === '' ? null : $v,
                $attrMap
            ),
        ];
    }
}
