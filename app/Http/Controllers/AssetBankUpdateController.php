<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PublishAction;
use App\Models\SearchCriteria;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SimpleXMLElement;

class AssetBankUpdateController extends Controller
{
    /*
     * Note: the Website Asset Bank Sync is intentionally left in a controlled state.
     * This controller writes to the AssetBank database, but the sync only triggers when
     * SELinux is in Enforcing mode — so the controller effectively does nothing in that
     * configuration. See notes in the project docs for context.
     */

    public function run_database_update(Request $request): JsonResponse
    {
        $days = max(1, (int) $request->query('days', 1));
        $updated = [];

        foreach (config('cranleigh.categories') as $category) {
            $updated[$category] = $this->update_SearchCriteriaXML($category, '-'.$days.' days');
        }

        return response()->json(['updated' => $updated]);
    }

    public function searchCriteria(?string $category_name = null): JsonResponse
    {
        if (empty($category_name)) {
            $categories = config('cranleigh.categories');
            throw new Exception('Category Name Not Set, choose one of: ['.implode(',', $categories).']', 500);
        }

        $action = PublishAction::with('searchCriteria')
            ->where('Path', 'LIKE', '%'.$category_name)
            ->first();

        return response()->json($action);
    }

    public function update_SearchCriteriaXML(string $category_name, string $previous_day): string
    {
        date_default_timezone_set('Europe/London');

        $action = PublishAction::with('searchCriteria')
            ->where('Path', 'LIKE', '%'.$category_name);

        $result = $action->get(['Id', 'SearchCriteriaId', 'Path'])->first();

        if ($result === null) {
            abort(400, 'No matching publish action found for category: '.$category_name);
        }

        // startTime is stored in milliseconds; date('Hi') gives HHMM as an integer
        $hour = (int) date('Hi');
        $action->update(['startTime' => $hour * 60 * 1000, 'IntervalTypeId' => 1]);

        $searchCriteriaQuery = SearchCriteria::query()->where('Id', $result->SearchCriteriaId);
        $searchCriteria = $searchCriteriaQuery->firstOrFail();

        $xml = new SimpleXMLElement($searchCriteria->SearchCriteriaXml);
        $yesterday = strtotime($previous_day) * 1000;
        $xml->dateRanges->lower = $yesterday;
        $xml->lowerDateToRefine->entry->value = date('c', strtotime($previous_day));

        $searchCriteriaQuery->update(['SearchCriteriaXml' => $xml->asXML()]);

        return date('r', (int) ($yesterday / 1000));
    }
}
