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

            $tempeClaim = $this->upsertClaim($posts->get('Tempe Goreng Tersedia'), $demoRecipient, [
                'jumlah_porsi' => 1,
                'status' => 'approved',
                'metode_pengambilan' => null,
                'kode_verifikasi' => null,
                'tanggal_klaim' => $today,
                'approved_at' => now(),
                'completed_at' => null,
            ]);

            if ($tempeClaim) {
                CourierDetail::where('claim_id', $tempeClaim->id)->delete();
            }

            $this->upsertClaim($posts->get('Roti Gandum Tersedia'), $demoRecipient, [
                'jumlah_porsi' => 1,
                'status' => 'completed',
                'metode_pengambilan' => 'ambil_sendiri',
                'kode_verifikasi' => '482901',
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
        $demoCourierClaim2 = null;

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

            if ($history['judul'] === 'Nasi Kotak Riwayat 08') {
                $demoCourierClaim2 = $claim;
            }
        }

        if ($demoCourierClaim) {
            CourierDetail::updateOrCreate(
                ['claim_id' => $demoCourierClaim->id],
                [
                    'platform' => 'Grab',
                    'nama_pengemudi' => 'Rizky Pratama',
                    'no_kendaraan' => 'B 9087 XY',
                    'kode_verifikasi_kurir' => 'GRAB88',
                    'foto_bukti' => 'seed/placeholder.webp',
                    'created_at' => now()->subDays(9),
                ]
            );
        }

        if ($demoCourierClaim2) {
            CourierDetail::updateOrCreate(
                ['claim_id' => $demoCourierClaim2->id],
                [
                    'platform' => 'Gojek',
                    'nama_pengemudi' => 'Budi Santoso',
                    'no_kendaraan' => 'D 1234 ABC',
                    'kode_verifikasi_kurir' => 'GJ-48213',
                    'foto_bukti' => 'seed/placeholder.webp',
                    'created_at' => now()->subDays(3),
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
                'kode_verifikasi' => null,
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
                'kode_verifikasi' => '482902',
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
                    'platform' => 'Gojek',
                    'nama_pengemudi' => 'Kurir Demo',
                    'no_kendaraan' => 'B 1234 CD',
                    'kode_verifikasi_kurir' => 'KURIR01',
                    'foto_bukti' => 'seed/placeholder.webp',
                    'created_at' => now(),
                ]
            );
        }

        $rotiClaim = Claim::where('metode_pengambilan', 'kurir_online')
            ->whereHas('post', fn ($q) => $q->where('judul', 'Roti Gandum Tersedia'))
            ->first();

        if ($rotiClaim) {
            CourierDetail::updateOrCreate(
                ['claim_id' => $rotiClaim->id],
                [
                    'platform' => 'Gojek',
                    'nama_pengemudi' => 'Pamungkas',
                    'no_kendaraan' => 'D 8753 VI',
                    'kode_verifikasi_kurir' => 'GH-732751',
                    'foto_bukti' => 'seed/placeholder.webp',
                    'created_at' => now()->subDay(),
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
