<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['recipient_id', 'judul', 'deskripsi', 'jumlah_porsi', 'batas_waktu', 'status'])]
class FoodRequest extends Model
{
    /**
     * The table has no updated_at column.
     */
    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'batas_waktu' => 'datetime',
        ];
    }

    /**
     * The recipient profile that published this request.
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(RecipientProfile::class, 'recipient_id');
    }
}
