<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PublishAction extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'publishaction';

    protected $fillable = ['ServerName', 'startTime', 'IntervalTypeId'];

    protected $connection = 'assetbank';

    protected $primaryKey = 'Id';

    public function searchCriteria(): HasOne
    {
        return $this->hasOne(SearchCriteria::class, 'Id', 'SearchCriteriaId');
    }

    protected function getRuntimeAttribute(): string
    {
        return date('Y-m-d H:i:s', (int) ($this->attributes['LastRunTime'] / 1000));
    }
}
