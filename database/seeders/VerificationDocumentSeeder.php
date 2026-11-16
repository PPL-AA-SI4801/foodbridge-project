<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\VerificationStatus;
use App\Models\User;
use App\Models\VerificationDocument;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VerificationDocumentSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pendingDonor = User::where('email', 'donor-pending@foodbridge.test')->first();
        $pendingRecipient = User::where('email', 'penerima-pending@foodbridge.test')->first();
        $activeDonor = User::where('email', 'donor@foodbridge.test')->first();

        if ($activeDonor) {
            VerificationDocument::updateOrCreate(
                ['user_id' => $activeDonor->id, 'jenis_dokumen' => 'nib'],
                [
                    'file_path' => "verification-documents/{$activeDonor->id}/nib.pdf",
                    'status' => VerificationStatus::Disetujui,
                    'catatan' => null,
                    'verified_at' => now(),
                ]
            );
        }

        if ($pendingDonor) {
            foreach (['nib', 'akta_pendirian'] as $jenis) {
                VerificationDocument::updateOrCreate(
                    ['user_id' => $pendingDonor->id, 'jenis_dokumen' => $jenis],
                    [
                        'file_path' => "verification-documents/{$pendingDonor->id}/{$jenis}.pdf",
                        'status' => VerificationStatus::Menunggu,
                        'catatan' => null,
                        'verified_at' => null,
                    ]
                );
            }

            VerificationDocument::updateOrCreate(
                ['user_id' => $pendingDonor->id, 'jenis_dokumen' => 'lainnya'],
                [
                    'file_path' => "verification-documents/{$pendingDonor->id}/lainnya.pdf",
                    'status' => VerificationStatus::Ditolak,
                    'catatan' => 'Dokumen tidak lengkap, mohon unggah ulang.',
                    'verified_at' => now(),
                ]
            );
        }

        if ($pendingRecipient) {
            foreach (['nib', 'izin_operasional'] as $jenis) {
                VerificationDocument::updateOrCreate(
                    ['user_id' => $pendingRecipient->id, 'jenis_dokumen' => $jenis],
                    [
                        'file_path' => "verification-documents/{$pendingRecipient->id}/{$jenis}.pdf",
                        'status' => VerificationStatus::Menunggu,
                        'catatan' => null,
                        'verified_at' => null,
                    ]
                );
            }
        }
    }
}
