<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WashingPoint extends Model
{
    protected $fillable = ['location_url', 'location_name'];
    public $timestamps = false;
}
