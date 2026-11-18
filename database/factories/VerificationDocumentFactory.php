<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VerificationDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VerificationDocument>
 */
class VerificationDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'jenis_dokumen' => 'nib',
            'file_path' => 'verification-documents/test/legalitas.pdf',
            'status' => 'menunggu',
            'catatan' => null,
            'verified_at' => null,
            'user_id' => User::factory(),
        ];
    }
}
