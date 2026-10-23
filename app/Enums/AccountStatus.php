<?php

namespace App\Enums;

enum AccountStatus: string
{
    case Menunggu = 'menunggu';
    case Aktif = 'aktif';
    case Ditolak = 'ditolak';
    case Diblokir = 'diblokir';

    /**
     * Determine whether the account may hold an authenticated session.
     */
    public function isActive(): bool
    {
        return $this === self::Aktif;
    }
}
