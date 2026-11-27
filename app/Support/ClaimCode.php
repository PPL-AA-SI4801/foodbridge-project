<?php

namespace App\Support;

use RuntimeException;

final class ClaimCode
{
    public const LENGTH = 6;

    /**
     * Buat kode 6 digit angka (000000–999999).
     */
    public static function generate(): string
    {
        return str_pad((string) random_int(0, 999999), self::LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Buat kode yang belum dipakai. Keunikan dijaga di aplikasi
     * karena ERD tidak memberi constraint unik pada kolom ini.
     *
     * @param  callable(string): bool  $exists
     */
    public static function generateUnique(callable $exists, int $maxAttempts = 20): string
    {
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $code = self::generate();

            if (! $exists($code)) {
                return $code;
            }
        }

        throw new RuntimeException('Gagal membuat kode verifikasi yang unik.');
    }
}
