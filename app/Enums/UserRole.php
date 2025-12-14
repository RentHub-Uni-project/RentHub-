<?php

namespace App\Enums;


enum UserRole: string
{
    case ADMIN = "admin";
    case OWNER = "owner";
    case TENANT = "tenant";
}
