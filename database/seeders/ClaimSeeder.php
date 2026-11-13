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

        // Additional historical claims so the admin table exercises pagination and every filter.
        $extraClaims = [
            [
                'post' => 'Buah Pisang Kedaluwarsa',
                'recipient' => $demoRecipient,
                'jumlah_porsi' => 1,
                'status' => 'rejected',
                'metode_pengambilan' => null,
                'tanggal_klaim' => $today->copy()->subDay(),
            ],
            [
                'post' => 'Buah Pisang Kedaluwarsa',
                'recipient' => $secondRecipient,
                'jumlah_porsi' => 2,
                'status' => 'pending',
                'metode_pengambilan' => null,
                'tanggal_klaim' => $today,
            ],
            [
                'post' => 'Nasi Ayam Geprek Tersedia',
                'recipient' => $secondRecipient,
                'jumlah_porsi' => 1,
                'status' => 'pending',
                'metode_pengambilan' => null,
                'tanggal_klaim' => $today,
            ],
            [
                'post' => 'Nasi Ayam Geprek Tersedia',
                'recipient' => $thirdRecipient,
                'jumlah_porsi' => 2,
                'status' => 'approved',
                'metode_pengambilan' => 'ambil_sendiri',
                'tanggal_klaim' => $today,
            ],
            [
                'post' => 'Tempe Goreng Tersedia',
                'recipient' => $secondRecipient,
                'jumlah_porsi' => 1,
                'status' => 'completed',
                'metode_pengambilan' => 'ambil_sendiri',
                'tanggal_klaim' => $today->copy()->subDays(3),
            ],
            [
                'post' => 'Tempe Goreng Tersedia',
                'recipient' => $thirdRecipient,
                'jumlah_porsi' => 1,
                'status' => 'rejected',
                'metode_pengambilan' => null,
                'tanggal_klaim' => $today->copy()->subDays(4),
            ],
            [
                'post' => 'Roti Gandum Tersedia',
                'recipient' => $secondRecipient,
                'jumlah_porsi' => 1,
                'status' => 'pending',
                'metode_pengambilan' => null,
                'tanggal_klaim' => $today,
            ],
            [
                'post' => 'Roti Gandum Tersedia',
                'recipient' => $thirdRecipient,
                'jumlah_porsi' => 2,
                'status' => 'approved',
                'metode_pengambilan' => 'kurir_online',
                'tanggal_klaim' => $today->copy()->subDay(),
            ],
            [
                'post' => 'Sayur Asem Habis',
                'recipient' => $demoRecipient,
                'jumlah_porsi' => 1,
                'status' => 'rejected',
                'metode_pengambilan' => null,
                'tanggal_klaim' => $today->copy()->subDays(5),
            ],
            [
                'post' => 'Sayur Asem Habis',
                'recipient' => $thirdRecipient,
                'jumlah_porsi' => 1,
                'status' => 'completed',
                'metode_pengambilan' => 'ambil_sendiri',
                'tanggal_klaim' => $today->copy()->subDays(6),
            ],
        ];

        foreach ($extraClaims as $extraClaim) {
            if ($extraClaim['recipient'] === null) {
                continue;
            }

            $this->upsertClaim($posts->get($extraClaim['post']), $extraClaim['recipient'], [
                'jumlah_porsi' => $extraClaim['jumlah_porsi'],
                'status' => $extraClaim['status'],
                'metode_pengambilan' => $extraClaim['metode_pengambilan'],
                'kode_verifikasi' => null,
                'tanggal_klaim' => $extraClaim['tanggal_klaim'],
                'approved_at' => in_array($extraClaim['status'], ['approved', 'completed'], true) ? now() : null,
                'completed_at' => $extraClaim['status'] === 'completed' ? now() : null,
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
