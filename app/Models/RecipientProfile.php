<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nama_lembaga', 'jenis_lembaga', 'alamat', 'lat', 'lng', 'user_id'])]
#[WithoutTimestamps]
class RecipientProfile extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
        ];
    }

    /**
     * The recipient account that owns this profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The requests published by this recipient.
     */
    public function foodRequests(): HasMany
    {
        return $this->hasMany(FoodRequest::class, 'recipient_id');
    }

    /**
     * The claims submitted by this recipient.
     */
    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class, 'recipient_id');
    }
}
