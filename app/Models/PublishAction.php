<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublishAction extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'publishaction';

    protected $fillable = ['ServerName'];

    protected $connection = 'assetbank';

    protected $primaryKey = 'Id';

    public function searchCriteria()
    {
        return $this->hasMany(SearchCriteria::class, 'Id', 'SearchCriteriaId');
    }

    protected function getruntimeAttribute()
    {
        return date('Y-m-d H:i:s', $this->attributes['LastRunTime'] / 1000);
    }
}
