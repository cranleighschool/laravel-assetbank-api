<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SearchCriteria extends Model
{
    protected $table = 'searchcriteria';

    public $timestamps = false;

    public $primaryKey = 'Id';
}
