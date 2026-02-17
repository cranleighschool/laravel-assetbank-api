<?php

namespace App\Http\Controllers;

use App\Models\PublishAction;
use App\Models\SearchCriteria;

class AssetBankUpdateController extends Controller
{
    /* So interestingly, the Website Asset Bank Sync seems to be working specifically when this controller is broken
     * Suggesting that the changes I'm sending to the Asset Bank Database in here, are incorrect and not picking up changes in Asset Bank itself!
     *
     * I'm going to leave it broken for now - the way to do that is to keep selinux to "Enforcing"!
     *
     */

    public function testSQL()
    {
        $actions = PublishAction::get();
        $list = [];
        foreach ($actions as $action) {
            $list[$action->Id] = $action->runtime;
        }

        /*
                foreach ($action as $a):
                    echo $a->LastRunTime;
                endforeach;
        */
        dump($list);
        exit();

        return response()->json($action);
    }

    public function run_database_update()
    {
        $updated = [];
        if (isset($_GET['days']) && $_GET['days'] > 0) {
            $days = $_GET['days'];
        } else {
            $days = 1;
        }
        foreach (config('cranleigh.categories') as $category) {
            $date = $this->update_SearchCriteriaXML($category, '-'.$days.' days');
            $updated[$category] = $date;
        }

        $result = ['updated' => $updated];

        return response()->json($result);
    }

    public function searchCriteria(?string $category_name = null)
    {
        if (empty($category_name)) {
            $categories = config('cranleigh.categories');
            throw new \Exception('Category Name Not Set, choose one of: ['.implode(',', $categories).']', 500);
        }

        $action = PublishAction::with('searchCriteria')->where('Path', 'LIKE',
            '%'.$category_name)->get()->first();

        return response()->json($action);
    }

    public function update_SearchCriteriaXML(string $category_name, string $previous_day)
    {
        date_default_timezone_set('Europe/London');

        $only_columns = ['Id', 'SearchCriteriaId', 'Path'];
        $action = PublishAction::with('searchCriteria')->where('Path', 'LIKE', '%'.$category_name);
        $result = $action->get($only_columns)->first();

        if (count($result) !== 1) {
            $result = 'no result';
            abort(400);
        }
        $publisher_id = ''.$result->Id;

        $hour = date('Hi');
        $new_startTime = $hour * 60 * 60 * 10;

        // If auto update doesn't work, then try changing the startTime...
        $action->update(['startTime' => $new_startTime, 'IntervalTypeId' => 1]);

        $SearchCriteria = SearchCriteria::where('Id', $result->SearchCriteriaId);
        $result = $SearchCriteria->get()->first();
        $newObj = new \SimpleXMLElement($result->SearchCriteriaXml);
        $yesterday = strtotime($previous_day) * 1000;
        $newObj->dateRanges->lower = $yesterday;
        $newObj->lowerDateToRefine->entry->value = date('c', strtotime($previous_day));
        $newXML = $newObj->asXML();

        $SearchCriteria->update(['SearchCriteriaXml' => $newXML]);

        return date('r', ($yesterday / 1000));
    }

    public function old_update_SearchCriteriaXML($category_name)
    {
        date_default_timezone_set('Europe/London');

        $this->db->where('Path', ['LIKE' => '%'.$category_name]);
        $searchId = $this->db->getOne('publishaction', 'Id, SearchCriteriaId, Path');
        if ($searchId == null) {
            throw new NotFoundException($request, $response);

            return false;
        }

        $this->db->where('Id', $searchId['Id']);
        $hour = date('Hi');

        $new_startTime = [
            'startTime' => ($hour) * 60 * 60 * 10,
        ];
        $this->db->update('publishaction', $new_startTime);

        $this->db->where('Id', $searchId['SearchCriteriaId']);
        $searchCriteria = $this->db->getOne('searchcriteria');

        $searchXML = $searchCriteria['SearchCriteriaXml'];
        if ($searchXML == null) {
            throw new NotFoundException($request, $response);
        }

        $new = new \SimpleXMLElement($searchXML);

        $yesterday = time() * 1000;
        $new->dateRanges->lower = $yesterday;
        $new->lowerDateToRefine->entry->value = date('c', time());

        $new_xml = $new->asXML();

        $data = [
            'SearchCriteriaXml' => $new_xml,
        ];
        $this->db->where('Id', $searchCriteria['Id']);
        $this->db->update('searchcriteria', $data);

        $this->db->where('Id', $searchCriteria['Id']);
        $newCriteria = $this->db->getOne('searchcriteria');
        $newCriteria['realStartTime'] = date('c');

        $this->db->where('Path', ['LIKE' => '%'.$args['path']]);
        $path = $this->db->getOne('publishaction');
        $path['searchCriteria'] = $newCriteria;

        //		$path = array("updated"=>"think so", "category" => $args['path']);
        //		return $this->view->render($response, $path, 200);
        return $newCriteria;
    }
}
