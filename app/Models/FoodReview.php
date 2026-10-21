<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['claim_id', 'rating', 'komentar', 'jenis_feedback', 'foto_bukti', 'status_penanganan', 'tindak_lanjut'])]
class FoodReview extends Model
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
            'created_at' => 'datetime',
        ];
    }

    /**
     * The claim this feedback belongs to.
     */
    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class, 'claim_id');
    }
}
