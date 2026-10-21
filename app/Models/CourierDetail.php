<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['claim_id', 'platform', 'nama_pengemudi', 'no_kendaraan', 'kode_verifikasi_kurir', 'foto_bukti'])]
class CourierDetail extends Model
{
    /**
     * The table has no updated_at column.
     */
    public const UPDATED_AT = null;

    /**
     * The claim this courier detail belongs to.
     */
    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class, 'claim_id');
    }
}
