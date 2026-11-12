<?php

namespace App\Support;

final class ClaimStatus
{
    /**
     * Filter keys shown in the UI and the stored values each key covers.
     *
     * The database stores English values while older rows may still use the
     * Indonesian equivalents, so both are accepted for every group.
     */
    public const GROUPS = [
        'menunggu' => ['pending', 'menunggu'],
        'disetujui' => ['approved', 'disetujui'],
        'selesai' => ['completed', 'selesai'],
        'ditolak' => ['rejected', 'ditolak'],
    ];

    /**
     * Human-readable labels for the filter keys.
     */
    public const LABELS = [
        'menunggu' => 'Menunggu',
        'disetujui' => 'Disetujui',
        'selesai' => 'Selesai',
        'ditolak' => 'Ditolak',
    ];

    /**
     * Stored status values mapped to the keys used by <x-status-badge>.
     */
    private const ALIASES = [
        'pending' => 'menunggu',
        'approved' => 'disetujui',
        'rejected' => 'ditolak',
        'completed' => 'selesai',
        'cancelled' => 'dibatalkan',
        'canceled' => 'dibatalkan',
    ];

    /**
     * Normalise a stored status value to the design-system badge key.
     */
    public static function normalize(?string $status): string
    {
        $status = (string) $status;

        return self::ALIASES[$status] ?? $status;
    }
}
