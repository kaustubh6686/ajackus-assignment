<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class CarbonFootprintCache extends Model
{
    protected $guarded = ['id', 'created_at', 'updated_at'];


    protected static function booted()
    {
        static::addGlobalScope('fresh', function (Builder $builder) {
            $builder->where('created_at', '>',Carbon::yesterday());
        });
    }

}
