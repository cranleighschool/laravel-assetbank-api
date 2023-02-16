<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PublishAction extends Model
{
    public $timestamps = false;

    protected $table = 'publishaction';
    protected $fillable = ['ServerName'];
    protected $primaryKey = "Id";

    public function searchCriteria()
    {
        return $this->hasMany(SearchCriteria::class, 'Id', 'SearchCriteriaId');
    }

    public function getruntimeAttribute()
    {

        return date("Y-m-d H:i:s", $this->attributes[ 'LastRunTime' ] / 1000);

    }


}
