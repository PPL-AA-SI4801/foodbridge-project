<?php

namespace Database\Seeders;

use App\Enums\FoodRequestStatus;
use App\Enums\UrgencyLevel;
use App\Models\DonorProfile;
use App\Models\FoodCategory;
use App\Models\FoodRequest;
use App\Models\RecipientProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class FoodRequestSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = FoodCategory::query()->pluck('id', 'nama_kategori');
        $recipients = RecipientProfile::query()->with('user')->get()->keyBy(fn ($r) => $r->user->email);
        $donors = DonorProfile::query()->with('user')->get()->keyBy(fn ($d) => $d->user->email);

        $demoRecipient = $recipients->get('penerima@foodbridge.test');
        $secondRecipient = $recipients->get('penerima2@foodbridge.test');
        $thirdRecipient = $recipients->get('penerima3@foodbridge.test');

        $donorDemo = $donors->get('donor@foodbridge.test');
        $donorSecond = $donors->get('donor2@foodbridge.test');

        $future = fn (int $days) => Carbon::now()->addDays($days);
        $past = fn (int $days) => Carbon::now()->subDays($days);

        if ($demoRecipient) {
            $requests = [
                [
                    'judul' => 'Kebutuhan Beras Mendesak',
                    'deskripsi' => 'Membutuhkan beras untuk 20 porsi makanan.',
                    'jumlah_porsi' => 5,
                    'urgency_level' => UrgencyLevel::Mendesak,
                    'metode_penerimaan' => 'ambil_sendiri',
                    'batas_waktu' => $future(2),
                    'status' => FoodRequestStatus::Open,
                    'category' => 'Karbohidrat',
                    'donor_id' => null,
                    'claim_id' => null,
                ],
                [
                    'judul' => 'Kebutuhan Susu Darurat',
                    'deskripsi' => 'Susu untuk anak-anak panti.',
                    'jumlah_porsi' => 3,
                    'urgency_level' => UrgencyLevel::Darurat,
                    'metode_penerimaan' => 'diantar_kurir',
                    'batas_waktu' => $future(1),
                    'status' => FoodRequestStatus::Open,
                    'category' => 'Minuman',
                    'donor_id' => null,
                    'claim_id' => null,
                ],
                [
                    'judul' => 'Kebutuhan Sayur Sedang Diproses',
                    'deskripsi' => 'Sayur untuk menu harian.',
                    'jumlah_porsi' => 4,
                    'urgency_level' => UrgencyLevel::Normal,
                    'metode_penerimaan' => 'fleksibel',
                    'batas_waktu' => $future(3),
                    'status' => FoodRequestStatus::InProgress,
                    'category' => 'Sayuran',
                    'donor_id' => $donorDemo?->id,
                    'claim_id' => null,
                ],
                [
                    'judul' => 'Kebutuhan Buah Terpenuhi',
                    'deskripsi' => 'Buah segar untuk camilan.',
                    'jumlah_porsi' => 2,
                    'urgency_level' => UrgencyLevel::Normal,
                    'metode_penerimaan' => 'ambil_sendiri',
                    'batas_waktu' => $future(5),
                    'status' => FoodRequestStatus::Fulfilled,
                    'category' => 'Buah-buahan',
                    'donor_id' => $donorSecond?->id,
                    'claim_id' => null,
                ],
                [
                    'judul' => 'Kebutuhan Protein Dibatalkan',
                    'deskripsi' => 'Protein hewani untuk lauk.',
                    'jumlah_porsi' => 3,
                    'urgency_level' => UrgencyLevel::Mendesak,
                    'metode_penerimaan' => 'ambil_sendiri',
                    'batas_waktu' => $future(4),
                    'status' => FoodRequestStatus::Cancelled,
                    'category' => 'Protein',
                    'donor_id' => null,
                    'claim_id' => null,
                ],
                [
                    'judul' => 'Kebutuhan Lemak Kedaluwarsa',
                    'deskripsi' => 'Minyak dan lemak sehat.',
                    'jumlah_porsi' => 2,
                    'urgency_level' => UrgencyLevel::Normal,
                    'metode_penerimaan' => 'diantar_kurir',
                    'batas_waktu' => $past(1),
                    'status' => FoodRequestStatus::Expired,
                    'category' => 'Lemak Sehat',
                    'donor_id' => null,
                    'claim_id' => null,
                ],
            ];

            foreach ($requests as $data) {
                FoodRequest::updateOrCreate(
                    ['judul' => $data['judul'], 'recipient_id' => $demoRecipient->id],
                    [
                        'recipient_id' => $demoRecipient->id,
                        'category_id' => $categories->get($data['category']),
                        'deskripsi' => $data['deskripsi'],
                        'jumlah_porsi' => $data['jumlah_porsi'],
                        'urgency_level' => $data['urgency_level'],
                        'metode_penerimaan' => $data['metode_penerimaan'],
                        'batas_waktu' => $data['batas_waktu'],
                        'status' => $data['status'],
                        'donor_id' => $data['donor_id'],
                        'claim_id' => $data['claim_id'],
                    ]
                );
            }
        }

        if ($secondRecipient) {
            FoodRequest::updateOrCreate(
                ['judul' => 'Kebutuhan Nasi Kotak Darurat', 'recipient_id' => $secondRecipient->id],
                [
                    'recipient_id' => $secondRecipient->id,
                    'category_id' => $categories->get('Karbohidrat'),
                    'deskripsi' => 'Nasi kotak untuk kegiatan sosial.',
                    'jumlah_porsi' => 10,
                    'urgency_level' => UrgencyLevel::Darurat,
                    'metode_penerimaan' => 'diantar_kurir',
                    'batas_waktu' => $future(1),
                    'status' => FoodRequestStatus::Open,
                    'donor_id' => null,
                    'claim_id' => null,
                ]
            );
        }

        if ($thirdRecipient) {
            FoodRequest::updateOrCreate(
                ['judul' => 'Kebutuhan Roti Tawar', 'recipient_id' => $thirdRecipient->id],
                [
                    'recipient_id' => $thirdRecipient->id,
                    'category_id' => $categories->get('Karbohidrat'),
                    'deskripsi' => 'Roti tawar untuk sarapan.',
                    'jumlah_porsi' => 5,
                    'urgency_level' => UrgencyLevel::Normal,
                    'metode_penerimaan' => 'ambil_sendiri',
                    'batas_waktu' => $past(2),
                    'status' => FoodRequestStatus::Expired,
                    'donor_id' => null,
                    'claim_id' => null,
                ]
            );
        }
    }
}
