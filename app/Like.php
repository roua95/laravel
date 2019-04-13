<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    //
    protected $fillable = [
        'user_id',
        'plan_id',
    ];
    public function plans()
    {
        return $this->morphedByMany('App\Plan', 'plan');
    }
}
