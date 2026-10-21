<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['jumlah_porsi', 'status', 'metode_pengambilan', 'kode_verifikasi', 'tanggal_klaim', 'approved_at', 'completed_at', 'post_id', 'recipient_id'])]
#[WithoutTimestamps]
class Claim extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_klaim' => 'date',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * The post this claim is made on.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(FoodPost::class, 'post_id');
    }

    /**
     * The recipient profile that submitted this claim.
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(RecipientProfile::class, 'recipient_id');
    }

    /**
     * The courier details, only filled in when an online courier is used.
     */
    public function courierDetail(): HasOne
    {
        return $this->hasOne(CourierDetail::class, 'claim_id');
    }
}
