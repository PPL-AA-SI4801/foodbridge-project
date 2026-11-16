<?php

namespace App\Enums;

enum UrgencyLevel: string
{
    case Normal = 'normal';
    case Mendesak = 'mendesak';
    case Darurat = 'darurat';

    /**
     * Human-readable Indonesian label for the urgency level.
     */
    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Mendesak => 'Mendesak',
            self::Darurat => 'Sangat Mendesak (Darurat)',
        };
    }

    /**
     * Short badge label.
     */
    public function badgeLabel(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Mendesak => 'Mendesak',
            self::Darurat => 'Darurat',
        };
    }

    /**
     * Tailwind badge classes for the urgency level.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Normal => 'bg-[#DBEAFE] text-[#1E40AF] border-[#93C5FD]',
            self::Mendesak => 'bg-[#FEF3C7] text-[#92400E] border-[#FCD34D]',
            self::Darurat => 'bg-[#FEE2E2] text-[#B91C1C] border-[#FCA5A5]',
        };
    }
}
