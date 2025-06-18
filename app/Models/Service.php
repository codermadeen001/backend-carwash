<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'package_type',
        'img_url',
        'price',
        'description',
        'duration'
    ];
}
