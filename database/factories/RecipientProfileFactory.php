<?php

namespace Database\Factories;

use App\Models\RecipientProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecipientProfile>
 */
class RecipientProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_lembaga' => 'Lembaga '.fake()->unique()->words(2, true),
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => fake()->address(),
            'lat' => -6.9147,
            'lng' => 107.6098,
            'user_id' => User::factory(),
        ];
    }
}
