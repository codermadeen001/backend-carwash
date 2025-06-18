<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'washing_point_id',
        'client_id',
        'washer_id',
        'time',
        'receipt',
        'plate_number',
        'status',
        'price'
    ];

    public function service() {
        return $this->belongsTo(Service::class);
    }

    public function washingPoint() {
        return $this->belongsTo(WashingPoint::class);
    }

    public function client() {
        return $this->belongsTo(AppUser::class, 'client_id');
    }

    public function washer() {
        return $this->belongsTo(AppUser::class, 'washer_id');
    }


    // In App\Models\Booking
public function washing_point()
{
    return $this->belongsTo(WashingPoint::class);
}





/*





    public function washer()
{
    return $this->belongsTo(AppUser::class, 'washer_id');
}

public function client()
{
    return $this->belongsTo(AppUser::class, 'client_id');
}

public function service()
{
    return $this->belongsTo(Service::class);
}

public function washingPoint()
{
    return $this->belongsTo(WashingPoint::class);
}





*/





}
