<?php

namespace App\Models;

use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['jenis_dokumen', 'file_path', 'status', 'catatan', 'verified_at', 'user_id'])]
#[WithoutTimestamps]
class VerificationDocument extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'status' => VerificationStatus::class,
        ];
    }

    /**
     * The human-readable label for the document type.
     */
    public function jenisDokumenLabel(): string
    {
        return match ($this->jenis_dokumen) {
            'nib' => 'NIB',
            'akta_pendirian' => 'Akta Pendirian',
            'izin_operasional' => 'Izin Operasional',
            'lainnya' => 'Dokumen Lainnya',
            default => 'Dokumen',
        };
    }

    /**
     * The account that uploaded this document.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
