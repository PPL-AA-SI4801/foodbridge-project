<?php

namespace App\Enums;

enum VerificationStatus: string
{
    case Menunggu = 'menunggu';
    case PerluKelengkapan = 'perlu_kelengkapan';
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';
}
