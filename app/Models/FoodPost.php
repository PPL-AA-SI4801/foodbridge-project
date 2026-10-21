<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['judul', 'deskripsi', 'foto', 'jumlah_porsi', 'sisa_porsi', 'alamat', 'lat', 'lng', 'batas_konsumsi', 'status', 'donor_id', 'category_id'])]
#[WithoutTimestamps]
class FoodPost extends Model
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
            'batas_konsumsi' => 'datetime',
        ];
    }

    /**
     * The donor profile that published this post.
     */
    public function donor(): BelongsTo
    {
        return $this->belongsTo(DonorProfile::class, 'donor_id');
    }

    /**
     * The nutrition category of the food in this post.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FoodCategory::class, 'category_id');
    }
}
