<?php

namespace App\Http\Controllers;

use App\Http\AssetBankApi;

class DigitalSignageController extends Controller
{
    public $assetBank;

    public function __construct(AssetBankApi $assetBank)
    {
        $this->assetBank = $assetBank;
    }

    public function setupSenior()
    {
        //
        //ASSETBANK INTEGRATION

        // Every category the image is included in MUST appear here otherwise it will not be pulled through.
        $incCats = [
            '14',
            '15',
            '16',
            '17',
            '85',
            '86',
            '87',
            '88',
            '97',
            '98',
            '179',
            '1681',
            '1701',
            '275',
            '276',
            '277',
            '278',
            '367',
            '1750',
            '2556',
            '2557',
            '2558',
            '2559',
            '2560',
            '2561',
            '2562',
        ];
        $exCats = ['1752'];

        $options = [
            'attribute_701' => 'Senior',
            'attribute_709' => 'Best',
            'pageSize' => 100,
            'sortAttributeId' => 7,
            'sortDescending' => 'true',
            'includeImplicitCategoryMembers' => 'false',
            'orientation' => 1,
            'descriptiveCategoryForm.categoryIds' => $incCats,
        ];

        return $this->assetBank->get('asset-search', $options);
    }
}
