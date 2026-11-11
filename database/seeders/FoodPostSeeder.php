<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\FoodPostStatus;
use App\Models\DonorProfile;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class FoodPostSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = FoodCategory::query()->pluck('id', 'nama_kategori');

        $activeDonors = DonorProfile::query()
            ->whereHas('user', fn ($q) => $q->where('status_akun', AccountStatus::Aktif->value))
            ->get()
            ->keyBy(fn ($donor) => $donor->user->email);

        $posts = [
            [
                'donor' => 'donor@foodbridge.test',
                'judul' => 'Nasi Ayam Geprek Tersedia',
                'kategori' => 'Karbohidrat',
                'deskripsi' => 'Nasi ayam geprek siap santap, porsi cukup untuk satu orang.',
                'jumlah_porsi' => 10,
                'sisa_porsi' => 10,
                'status' => FoodPostStatus::Tersedia,
                'days' => 2,
            ],
            [
                'donor' => 'donor@foodbridge.test',
                'judul' => 'Sayur Asem Habis',
                'kategori' => 'Sayuran',
                'deskripsi' => 'Sayur asem segar, stok sudah habis.',
                'jumlah_porsi' => 5,
                'sisa_porsi' => 0,
                'status' => FoodPostStatus::Habis,
                'days' => 1,
            ],
            [
                'donor' => 'donor@foodbridge.test',
                'judul' => 'Buah Pisang Kedaluwarsa',
                'kategori' => 'Buah-buahan',
                'deskripsi' => 'Pisang matang, sudah melewati batas konsumsi.',
                'jumlah_porsi' => 8,
                'sisa_porsi' => 8,
                'status' => FoodPostStatus::Kedaluwarsa,
                'days' => -1,
            ],
            [
                'donor' => 'donor2@foodbridge.test',
                'judul' => 'Tempe Goreng Tersedia',
                'kategori' => 'Protein',
                'deskripsi' => 'Tempe goreng renyah, cocok untuk lauk.',
                'jumlah_porsi' => 6,
                'sisa_porsi' => 6,
                'status' => FoodPostStatus::Tersedia,
                'days' => 3,
            ],
            [
                'donor' => 'donor2@foodbridge.test',
                'judul' => 'Teh Manis Habis',
                'kategori' => 'Minuman',
                'deskripsi' => 'Teh manis dingin, stok habis.',
                'jumlah_porsi' => 12,
                'sisa_porsi' => 0,
                'status' => FoodPostStatus::Habis,
                'days' => 1,
            ],
            [
                'donor' => 'donor3@foodbridge.test',
                'judul' => 'Alpukat Mentega Kedaluwarsa',
                'kategori' => 'Lemak Sehat',
                'deskripsi' => 'Alpukat mentega sudah kedaluwarsa.',
                'jumlah_porsi' => 4,
                'sisa_porsi' => 4,
                'status' => FoodPostStatus::Kedaluwarsa,
                'days' => -2,
            ],
            [
                'donor' => 'donor3@foodbridge.test',
                'judul' => 'Roti Gandum Tersedia',
                'kategori' => 'Karbohidrat',
                'deskripsi' => 'Roti gandum sehat untuk sarapan.',
                'jumlah_porsi' => 7,
                'sisa_porsi' => 7,
                'status' => FoodPostStatus::Tersedia,
                'days' => 2,
            ],
        ];

        foreach ($posts as $data) {
            $donor = $activeDonors->get($data['donor']);
            $categoryId = $categories->get($data['kategori']);

            if (! $donor || ! $categoryId) {
                continue;
            }

            FoodPost::updateOrCreate(
                ['judul' => $data['judul'], 'donor_id' => $donor->id],
                [
                    'deskripsi' => $data['deskripsi'],
                    'foto' => 'seed/placeholder.webp',
                    'jumlah_porsi' => $data['jumlah_porsi'],
                    'sisa_porsi' => $data['sisa_porsi'],
                    'alamat' => $donor->alamat,
                    'catatan_kurir' => null,
                    'lat' => $donor->lat,
                    'lng' => $donor->lng,
                    'batas_konsumsi' => Carbon::now()->addDays($data['days']),
                    'status' => $data['status'],
                    'donor_id' => $donor->id,
                    'category_id' => $categoryId,
                ]
            );
        }
    }
}
