<?php

namespace App\Enums;

/**
 * Canonical in-app notification event types.
 *
 * The values are intentionally kept as the historical canonical strings so
 * existing rows and the notification presenter continue to work. New code must
 * use these cases instead of hard-coded strings.
 */
enum NotificationType: string
{
    case Account = 'akun';
    case Claim = 'klaim';
    case FoodPost = 'post';
    case FoodRequest = 'request';
    case Report = 'laporan';
}
