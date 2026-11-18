<?php

namespace Database\Seeders;

use App\Models\Claim;
use App\Models\CourierDetail;
use App\Models\FoodPost;
use App\Models\FoodReview;
use App\Models\RecipientProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ClaimSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $recipients = RecipientProfile::query()
            ->with('user')
            ->get()
            ->keyBy(fn ($r) => $r->user->email);

        $posts = FoodPost::query()->get()->keyBy('judul');

        $demoRecipient = $recipients->get('penerima@foodbridge.test');
        $secondRecipient = $recipients->get('penerima2@foodbridge.test');
        $thirdRecipient = $recipients->get('penerima3@foodbridge.test');

        $today = Carbon::today();

        // Recipient demo: 3 non-rejected claims today to exhaust daily quota.
        if ($demoRecipient) {
            $this->upsertClaim($posts->get('Nasi Ayam Geprek Tersedia'), $demoRecipient, [
                'jumlah_porsi' => 2,
                'status' => 'pending',
                'metode_pengambilan' => null,
                'kode_verifikasi' => null,
                'tanggal_klaim' => $today,
                'approved_at' => null,
                'completed_at' => null,
            ]);

            $this->upsertClaim($posts->get('Tempe Goreng Tersedia'), $demoRecipient, [
                'jumlah_porsi' => 1,
                'status' => 'approved',
                'metode_pengambilan' => 'ambil_sendiri',
                'kode_verifikasi' => 'APRV01',
                'tanggal_klaim' => $today,
                'approved_at' => now(),
                'completed_at' => null,
            ]);

            $this->upsertClaim($posts->get('Roti Gandum Tersedia'), $demoRecipient, [
                'jumlah_porsi' => 1,
                'status' => 'completed',
                'metode_pengambilan' => 'ambil_sendiri',
                'kode_verifikasi' => 'CMPL01',
                'tanggal_klaim' => $today,
                'approved_at' => now()->subDay(),
                'completed_at' => now(),
            ]);
        }

        // Historical claim history for the demo recipient: more than ten rows,
        // with every status represented at least once.
        $historyClaims = [
            [
                'judul' => 'Nasi Kotak Riwayat 01',
                'jumlah_porsi' => 1,
                'status' => 'rejected',
                'metode_pengambilan' => null,
                'kode_verifikasi' => null,
                'days_ago' => 10,
                'approved_at' => null,
                'completed_at' => null,
            ],
            [
                'judul' => 'Nasi Kotak Riwayat 02',
                'jumlah_porsi' => 2,
                'status' => 'approved',
                'metode_pengambilan' => 'kurir_online',
                'kode_verifikasi' => null,
                'days_ago' => 9,
                'approved_at' => now()->subDays(9),
                'completed_at' => null,
            ],
            [
                'judul' => 'Nasi Kotak Riwayat 03',
                'jumlah_porsi' => 1,
                'status' => 'completed',
                'metode_pengambilan' => 'ambil_sendiri',
                'kode_verifikasi' => '482913',
                'days_ago' => 8,
                'approved_at' => now()->subDays(8),
                'completed_at' => now()->subDays(7),
            ],
            [
                'judul' => 'Nasi Kotak Riwayat 04',
                'jumlah_porsi' => 1,
                'status' => 'completed',
                'metode_pengambilan' => 'ambil_sendiri',
                'kode_verifikasi' => '731204',
                'days_ago' => 7,
                'approved_at' => now()->subDays(7),
                'completed_at' => now()->subDays(6),
            ],
            [
                'judul' => 'Nasi Kotak Riwayat 05',
                'jumlah_porsi' => 2,
                'status' => 'rejected',
                'metode_pengambilan' => null,
                'kode_verifikasi' => null,
                'days_ago' => 6,
                'approved_at' => null,
                'completed_at' => null,
            ],
            [
                'judul' => 'Nasi Kotak Riwayat 06',
                'jumlah_porsi' => 1,
                'status' => 'approved',
                'metode_pengambilan' => 'ambil_sendiri',
                'kode_verifikasi' => '590127',
                'days_ago' => 5,
                'approved_at' => now()->subDays(5),
                'completed_at' => null,
            ],
            [
                'judul' => 'Nasi Kotak Riwayat 07',
                'jumlah_porsi' => 1,
                'status' => 'completed',
                'metode_pengambilan' => 'ambil_sendiri',
                'kode_verifikasi' => '864205',
                'days_ago' => 4,
                'approved_at' => now()->subDays(4),
                'completed_at' => now()->subDays(3),
            ],
            [
                'judul' => 'Nasi Kotak Riwayat 08',
                'jumlah_porsi' => 1,
                'status' => 'completed',
                'metode_pengambilan' => 'kurir_online',
                'kode_verifikasi' => null,
                'days_ago' => 3,
                'approved_at' => now()->subDays(3),
                'completed_at' => now()->subDays(2),
            ],
            [
                'judul' => 'Nasi Kotak Riwayat 09',
                'jumlah_porsi' => 1,
                'status' => 'rejected',
                'metode_pengambilan' => null,
                'kode_verifikasi' => null,
                'days_ago' => 2,
                'approved_at' => null,
                'completed_at' => null,
            ],
        ];

        $demoCourierClaim = null;

        foreach ($historyClaims as $history) {
            $post = $posts->get($history['judul']);

            if (! $post || ! $demoRecipient) {
                continue;
            }

            $claim = $this->upsertClaim($post, $demoRecipient, [
                'jumlah_porsi' => $history['jumlah_porsi'],
                'status' => $history['status'],
                'metode_pengambilan' => $history['metode_pengambilan'],
                'kode_verifikasi' => $history['kode_verifikasi'],
                'tanggal_klaim' => $today->copy()->subDays($history['days_ago']),
                'approved_at' => $history['approved_at'],
                'completed_at' => $history['completed_at'],
            ]);

            if ($history['judul'] === 'Nasi Kotak Riwayat 02') {
                $demoCourierClaim = $claim;
            }
        }

        if ($demoCourierClaim) {
            CourierDetail::updateOrCreate(
                ['claim_id' => $demoCourierClaim->id],
                [
                    'platform' => 'GrabExpress',
                    'nama_pengemudi' => 'Rizky Pratama',
                    'no_kendaraan' => 'B 9087 XY',
                    'kode_verifikasi_kurir' => 'GRAB88',
                    'foto_bukti' => 'seed/placeholder.webp',
                    'created_at' => now()->subDays(9),
                ]
            );
        }

        // Rejected claim for a different recipient.
        if ($secondRecipient && $posts->get('Sayur Asem Habis')) {
            $this->upsertClaim($posts->get('Sayur Asem Habis'), $secondRecipient, [
                'jumlah_porsi' => 1,
                'status' => 'rejected',
                'metode_pengambilan' => null,
                'kode_verifikasi' => null,
                'tanggal_klaim' => $today->copy()->subDay(),
                'approved_at' => null,
                'completed_at' => null,
            ]);
        }

        // Online claim (kurir) with courier detail.
        $onlineClaim = null;
        if ($thirdRecipient && $posts->get('Alpukat Mentega Kedaluwarsa')) {
            $onlineClaim = $this->upsertClaim($posts->get('Alpukat Mentega Kedaluwarsa'), $thirdRecipient, [
                'jumlah_porsi' => 1,
                'status' => 'approved',
                'metode_pengambilan' => 'kurir_online',
                'kode_verifikasi' => 'ONLINE1',
                'tanggal_klaim' => $today,
                'approved_at' => now(),
                'completed_at' => null,
            ]);
        }

        // Completed claim with review.
        $reviewClaim = null;
        if ($secondRecipient && $posts->get('Teh Manis Habis')) {
            $reviewClaim = $this->upsertClaim($posts->get('Teh Manis Habis'), $secondRecipient, [
                'jumlah_porsi' => 2,
                'status' => 'completed',
                'metode_pengambilan' => 'ambil_sendiri',
                'kode_verifikasi' => 'REVW01',
                'tanggal_klaim' => $today->copy()->subDays(2),
                'approved_at' => now()->subDays(2),
                'completed_at' => now()->subDay(),
            ]);
        }

        // Courier detail for online claim.
        if ($onlineClaim) {
            CourierDetail::updateOrCreate(
                ['claim_id' => $onlineClaim->id],
                [
                    'platform' => 'GoSend',
                    'nama_pengemudi' => 'Kurir Demo',
                    'no_kendaraan' => 'B 1234 CD',
                    'kode_verifikasi_kurir' => 'KURIR01',
                    'foto_bukti' => 'seed/placeholder.webp',
                    'created_at' => now(),
                ]
            );
        }

        // Food review for completed claim with review.
        if ($reviewClaim) {
            FoodReview::updateOrCreate(
                ['claim_id' => $reviewClaim->id],
                [
                    'rating' => 5,
                    'komentar' => 'Makanan enak dan pengantaran tepat waktu.',
                    'jenis_feedback' => 'positif',
                    'foto_bukti' => 'seed/placeholder.webp',
                    'status_penanganan' => 'selesai',
                    'tindak_lanjut' => null,
                    'created_at' => now(),
                ]
            );
        }

        // Food review for recipient demo completed claim too.
        $demoCompleted = Claim::where('recipient_id', $demoRecipient?->id)
            ->where('status', 'completed')
            ->first();
        if ($demoCompleted) {
            FoodReview::updateOrCreate(
                ['claim_id' => $demoCompleted->id],
                [
                    'rating' => 4,
                    'komentar' => 'Terima kasih, makanan masih layak konsumsi.',
                    'jenis_feedback' => 'positif',
                    'foto_bukti' => 'seed/placeholder.webp',
                    'status_penanganan' => null,
                    'tindak_lanjut' => null,
                    'created_at' => now(),
                ]
            );
        }
    }

    private function upsertClaim(?FoodPost $post, RecipientProfile $recipient, array $attributes): ?Claim
    {
        if (! $post) {
            return null;
        }

        return Claim::updateOrCreate(
            ['post_id' => $post->id, 'recipient_id' => $recipient->id],
            $attributes + [
                'post_id' => $post->id,
                'recipient_id' => $recipient->id,
            ]
        );
    }
}
