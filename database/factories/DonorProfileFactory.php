<?php

namespace Database\Factories;

use App\Models\DonorProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DonorProfile>
 */
class DonorProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_usaha' => 'Usaha '.fake()->unique()->words(2, true),
            'jenis_usaha' => 'Restoran',
            'alamat' => fake()->address(),
            'lat' => -6.9175,
            'lng' => 107.6191,
            'user_id' => User::factory(),
        ];
    }
}
