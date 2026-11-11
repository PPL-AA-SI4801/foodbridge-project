<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(FoodCategorySeeder::class);
        $this->call(DemoUserSeeder::class);
        $this->call(VerificationDocumentSeeder::class);
        $this->call(FoodPostSeeder::class);
        $this->call(ClaimSeeder::class);
        $this->call(FoodRequestSeeder::class);
        $this->call(InAppNotificationSeeder::class);
    }
}
