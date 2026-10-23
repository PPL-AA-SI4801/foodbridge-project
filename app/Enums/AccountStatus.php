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
            self::Menunggu => 'Your account is waiting for administrator verification.',
            self::Ditolak => 'Your registration was rejected by the administrator.',
            self::Diblokir => 'Your account has been blocked.',
        };
    }
}
