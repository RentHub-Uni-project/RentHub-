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
        return $this->status == BookingUpdateRequestStatus::PENDING->value;
    }

    public function isApparoved()
    {
        return $this->status == BookingUpdateRequestStatus::APPROVED->value;
    }

    public function isCancelled()
    {
        return $this->status == BookingUpdateRequestStatus::CANCELLED->value;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?: $this->getRouteKeyName();

        $model = $this->where($field, $value)->first();

        if (!$model) {
            abort(response()->json([
                'message' => 'update request not found',
            ], 404));
        }

        return $model;
    }
}
