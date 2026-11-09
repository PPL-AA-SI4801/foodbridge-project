<?php

namespace App\Enums;

enum FoodRequestStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Fulfilled = 'fulfilled';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    /**
     * Human-readable Indonesian label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Open => 'Terbuka',
            self::InProgress => 'Sedang Diproses',
            self::Fulfilled => 'Terpenuhi',
            self::Expired => 'Kedaluwarsa',
            self::Cancelled => 'Dibatalkan',
        };
    }

    /**
     * Determine whether this request is still open for Donor fulfillment.
     */
    public function isOpen(): bool
    {
        return $this === self::Open;
    }

    /**
     * Determine whether this request counts toward active/open request limits.
     */
    public function isActive(): bool
    {
        return in_array($this, [self::Open, self::InProgress], true);
    }
}
