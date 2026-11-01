<?php

namespace Database\Seeders;

use App\Models\FoodCategory;
use Illuminate\Database\Seeder;

class FoodCategorySeeder extends Seeder
{
    /**
     * Seed the nutrition categories used by food posts.
     */
    public function run(): void
    {
        $categories = [
            'Karbohidrat',
            'Protein',
            'Sayuran',
            'Buah-buahan',
            'Lemak Sehat',
            'Minuman',
        ];

        foreach ($categories as $category) {
            FoodCategory::query()->firstOrCreate([
                'nama_kategori' => $category,
            ]);
        }
    }
}
