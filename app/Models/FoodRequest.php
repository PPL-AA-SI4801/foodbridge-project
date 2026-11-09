<?php

namespace App\Models;

use App\Enums\FoodRequestStatus;
use App\Enums\UrgencyLevel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'recipient_id',
    'category_id',
    'judul',
    'deskripsi',
    'jumlah_porsi',
    'urgency_level',
    'metode_penerimaan',
    'batas_waktu',
    'status',
    'donor_id',
    'claim_id',
])]
class FoodRequest extends Model
{
    /**
     * Maximum number of simultaneous open requests a single Penerima may hold.
     */
    public const MAX_OPEN_REQUESTS = 3;

    /**
     * The table has no updated_at column.
     */
    public const UPDATED_AT = null;

    /**
     * The default attribute values for a new food request.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'open',
        'urgency_level' => 'mendesak',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'batas_waktu' => 'datetime',
            'status' => FoodRequestStatus::class,
            'urgency_level' => UrgencyLevel::class,
        ];
    }

    /**
     * Limit the query to active/open requests that have not passed their deadline.
     */
    public function scopeOpenBoard(Builder $query): void
    {
        $query->where('status', FoodRequestStatus::Open->value)
            ->where('batas_waktu', '>', now());
    }

    /**
     * Limit the query to open requests that have passed their deadline.
     */
    public function scopeExpired(Builder $query): void
    {
        $query->where('batas_waktu', '<=', now())
            ->where('status', FoodRequestStatus::Open->value);
    }

    /**
     * Synchronously mark any overdue open requests as expired.
     */
    public static function expireOverdue(): int
    {
        return static::query()
            ->expired()
            ->update(['status' => FoodRequestStatus::Expired->value]);
    }

    /**
     * Determine whether this request is past its deadline.
     */
    public function isPastDeadline(): bool
    {
        return $this->batas_waktu !== null && $this->batas_waktu->isPast();
    }

    /**
     * Human-readable label for preferred pickup/delivery method.
     */
    public function metodePenerimaanLabel(): string
    {
        return match ($this->metode_penerimaan) {
            'ambil_sendiri' => 'Ambil Sendiri',
            'diantar_kurir' => 'Diantar / Kurir',
            default => 'Fleksibel (Ambil / Diantar)',
        };
    }

    /**
     * The recipient profile that published this request.
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(RecipientProfile::class, 'recipient_id');
    }

    /**
     * The nutrition/food category requested.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FoodCategory::class, 'category_id');
    }

    /**
     * The donor profile fulfilling this request, if any.
     */
    public function donor(): BelongsTo
    {
        return $this->belongsTo(DonorProfile::class, 'donor_id');
    }

    /**
     * The claim created when a donor fulfills this request.
     */
    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class, 'claim_id');
    }
}
