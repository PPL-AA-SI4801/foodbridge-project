<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nama_kategori'])]
#[WithoutTimestamps]
class FoodCategory extends Model
{
    /**
     * The food posts grouped under this category.
     */
    public function foodPosts(): HasMany
    {
        return $this->hasMany(FoodPost::class, 'category_id');
    }
}
