<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['claim_id', 'platform', 'nama_pengemudi', 'no_kendaraan', 'kode_verifikasi_kurir', 'foto_bukti'])]
class CourierDetail extends Model
{
    public const PLATFORM_GOJEK = 'Gojek';

    public const PLATFORM_GRAB = 'Grab';

    public const PLATFORMS = [
        self::PLATFORM_GOJEK,
        self::PLATFORM_GRAB,
    ];

    /**
     * The table has no updated_at column.
     */
    public const UPDATED_AT = null;

    /**
     * Get the public URL for the order proof photo.
     */
    protected function fotoBuktiUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->foto_bukti ? Storage::disk('public')->url($this->foto_bukti) : null,
        );
    }

    /**
     * The claim this courier detail belongs to.
     */
    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class, 'claim_id');
    }
}
