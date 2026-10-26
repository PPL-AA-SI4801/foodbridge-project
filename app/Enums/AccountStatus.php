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

    /**
     * The message shown to an account that may not sign in, if any.
     */
    public function refusalMessage(): ?string
    {
        return match ($this) {
            self::Aktif => null,
            self::Menunggu => 'Akun Anda sedang menunggu verifikasi administrator.',
            self::Ditolak => 'Pendaftaran Anda ditolak oleh administrator.',
            self::Diblokir => 'Akun Anda telah diblokir.',
        };
    }
}
