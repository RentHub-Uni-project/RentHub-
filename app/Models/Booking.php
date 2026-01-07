<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        "start_date" => "date",
        "end_date" => "date",
    ];

    public function apartment()
    {
        return $this->belongsTo(Apartment::class, "apartment_id");
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, "tenant_id");
    }

    public function isApproved()
    {
        return $this->status == BookingStatus::APPROVED->value;
    }
    public function isRejected()
    {
        return $this->status == BookingStatus::REJECTED->value;
    }
    public function isPending()
    {
        return $this->status == BookingStatus::PENDING->value;
    }

    public function isCancelled()
    {
        return $this->status == BookingStatus::CANCELLED->value;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?: $this->getRouteKeyName();

        $model = $this->where($field, $value)->first();

        if (!$model) {
            abort(response()->json([
                'message' => 'booking not found',
            ], 404));
        }

        return $model;
    }

    public static function calculateTotalPrice($start_date, $end_date, $price_per_night)
    {
        $days = Carbon::parse($start_date)->diffInDays(Carbon::parse($end_date));
        $total_price = $days * $price_per_night;

        return $total_price;
    }
}
