<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\AssetBankApi;

class DigitalSignageController extends Controller
{
    public function __construct(private readonly AssetBankApi $assetBank) {}

    public function setupSenior(): mixed
    {
        // Every category the image belongs to must appear in this list,
        // otherwise it will not be returned by the search.
        $includedCategories = [
            '14', '15', '16', '17', '85', '86', '87', '88',
            '97', '98', '179', '1681', '1701', '275', '276',
            '277', '278', '367', '1750', '2556', '2557', '2558',
            '2559', '2560', '2561', '2562',
        ];

        return $this->assetBank->get('asset-search', [
            'attribute_701' => 'Senior',
            'attribute_709' => 'Best',
            'pageSize' => 100,
            'sortAttributeId' => 7,
            'sortDescending' => 'true',
            'includeImplicitCategoryMembers' => 'false',
            'orientation' => 1,
            'descriptiveCategoryForm.categoryIds' => $includedCategories,
        ]);
    }
}
