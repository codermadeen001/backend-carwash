<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClothItem extends Model
{
    use HasFactory;
     //
     protected $fillable=['name','email', 'category','description', 'image_link' ,'brand','favorite','purchase_date','price',];
    
}


 