<?php

namespace App\Models;

use App\Enums\BookingStatus;
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
        return $this->status == BookingStatus::APPROVED;
    }
    public function isRejected()
    {
        return $this->status == BookingStatus::REJECTED;
    }
    public function isPending()
    {
        return $this->status == BookingStatus::PENDING;
    }

    public static function calculateTotalPrice($start_date, $end_date, $price_per_night)
    {
        $interval = $start_date->diff($end_date);
        $total_price = $interval->days * $price_per_night;

        return $total_price;
    }
}
