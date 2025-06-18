<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WasherRating extends Model
{
    protected $fillable = ['user_id', 'washer_id', 'rating'];

    public function user() {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function washer() {
        return $this->belongsTo(AppUser::class, 'washer_id');
    }
}
