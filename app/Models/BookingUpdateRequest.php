<?php

namespace App\Models;

use App\Enums\BookingUpdateRequestStatus;
use Illuminate\Database\Eloquent\Model;

class BookingUpdateRequest extends Model
{

    protected $guarded = [];

    public function booking()
    {
        return $this->belongsTo(Booking::class, "booking_id");
    }

    public function isPending()
    {
        return $this->status == BookingUpdateRequestStatus::PENDING;
    }
}
