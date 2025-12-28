<?php

namespace App\Services;

use App\Models\Notification;
use Carbon\Carbon;

class NotificationService
{
    public static function createNotification($userId, $type, $title, $message, $referenceId = null)
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'reference_id' => $referenceId,
            'title' => $title,
            'message' => $message,
            'created_at' => Carbon::now()
        ]);
    }
}
