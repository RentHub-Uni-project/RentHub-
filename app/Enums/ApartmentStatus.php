<?php

namespace App\Enums;


enum ApartmentStatus: string
{
    case PENDING = "pending";
    case APPROVED = "approved";
    case REJECTED = "rejected";
    case CANCELLED = "cancelled";
}
