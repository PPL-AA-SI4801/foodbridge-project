<?php

namespace App\Models;

use App\Support\ClaimStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['jumlah_porsi', 'status', 'metode_pengambilan', 'kode_verifikasi', 'tanggal_klaim', 'approved_at', 'completed_at', 'post_id', 'recipient_id', 'user_id'])]
#[WithoutTimestamps]
class Claim extends Model
{
    public const METHOD_AMBIL_SENDIRI = 'ambil_sendiri';

    public const METHOD_KURIR_ONLINE = 'kurir_online';

    // Alias konstanta untuk kompatibilitas
    public const METHOD_KURIR = self::METHOD_KURIR_ONLINE;

    public const PICKUP_METHODS = [
        self::METHOD_AMBIL_SENDIRI,
        self::METHOD_KURIR_ONLINE,
        'kurir',
    ];

    /**
     * Determine whether a pickup method has already been chosen for this claim.
     */
    public function hasPickupMethod(): bool
    {
        return ! empty($this->metode_pengambilan);
    }

    /**
     * Determine whether the claim has been approved.
     */
    public function isApproved(): bool
    {
        return in_array($this->status, ClaimStatus::GROUPS['disetujui'], true);
    }

    /**
     * Determine whether the related food post has passed its consumption limit.
     */
    public function isFoodExpired(): bool
    {
        $foodPost = $this->post;

        return $foodPost?->batas_konsumsi !== null && $foodPost->batas_konsumsi->isPast();
    }

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
     * Alias untuk post agar kompatibel dengan pemanggilan foodPost() di controller.
     */
    public function foodPost(): BelongsTo
    {
        return $this->post();
    }

    /**
     * The recipient profile that submitted this claim.
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(RecipientProfile::class, 'recipient_id');
    }

    /**
     * Relasi langsung ke User (penerima) untuk keperluan notifikasi.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The courier details, only filled in when an online courier is used.
     */
    public function courierDetail(): HasOne
    {
        return $this->hasOne(CourierDetail::class, 'claim_id');
    }

    /**
     * The review or report left on this claim.
     */
    public function foodReview(): HasOne
    {
        return $this->hasOne(FoodReview::class, 'claim_id');
    }
}
