<?php

namespace App\Models;

use App\Enums\ApartmentStatus;
use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apartment extends Model
{
    use HasFactory;

    protected $table = 'apartments';

    protected $fillable = [
        'owner_id',
        'title',
        'description',
        'address',
        'price_per_night',
        'max_guests',
        'bedrooms',
        'bathrooms',
        'governorate',
        'latitude',
        'longitude',
        'status',
    ];

    protected $casts = [
        'price_per_night' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    protected $appends = ['average_rating'];


    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function images()
    {
        return $this->hasMany(ApartmentImage::class, 'apartment_id')
        ->orderBy('display_order');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'apartment_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'apartment_id');
    }
    public function favorites()
    {
        return $this->hasMany(FavoriteApartment::class, 'apartment_id');
    }

    public function getAverageRatingAttribute()
    {
        return round(
            $this->reviews()->avg('rating'),
            1
        );
    }


    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?: $this->getRouteKeyName();

        $model = $this->where($field, $value)->first();

        if (!$model) {
            abort(response()->json([
                'message' => 'apartment not found',
            ], 404));
        }

        return $model;
    }

    public function isPending()
    {
        return $this->status == ApartmentStatus::PENDING->value;
    }
    public function isRejected()
    {
        return $this->status == ApartmentStatus::REJECTED->value;
    }
    public function isApproved()
    {
        return $this->status == ApartmentStatus::APPROVED->value;
    }

    public function checkAvailability($start_date, $end_date)
    {
        $overlap = Booking::where('apartment_id', $this->id)
            ->where('status', BookingStatus::APPROVED)
            ->where(function ($query) use ($start_date, $end_date) {
                $query->whereBetween('start_date', [$start_date, $end_date])
                    ->orWhereBetween('end_date', [$start_date, $end_date])
                    ->orWhere(function ($q) use ($start_date, $end_date) {
                        $q->where('start_date', '<', $start_date)
                            ->where('end_date', '>', $end_date);
                    });
            })
            ->exists();

        if ($overlap) {
            return [
                'available' => false,
                'message' => 'Selected dates are not available'
            ];
        }

        return ['available' => true];
    }
}
