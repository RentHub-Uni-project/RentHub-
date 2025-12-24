<?php

namespace App\Enums;


enum BookingUpdateRequestStatus: string
{
    case PENDING = "pending";
    case APPROVED = "approved";
    case REJECTED = "rejected";
    case CANCELLED = "cancelled";
}
