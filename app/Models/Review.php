<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    /**
     * Table name (explicit because of non-standard naming)
     */
    protected $table = 'reviews';


    protected $fillable = [
        'booking_id',
        'apartment_id',
        'tenant_id',
        'rating',
        'comment',
    ];


    protected $casts = [
        'rating' => 'integer',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function apartment()
    {
        return $this->belongsTo(Apartment::class, 'apartment_id');
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?: $this->getRouteKeyName();

        $model = $this->where($field, $value)->first();

        if (!$model) {
            abort(response()->json([
                'message' => 'review not found',
            ], 404));
        }

        return $model;
    }
}
