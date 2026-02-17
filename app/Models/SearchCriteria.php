<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchCriteria extends Model
{
    protected $table = 'searchcriteria';

    protected $connection = 'assetbank';

    public $timestamps = false;

    public $primaryKey = 'Id';
}
