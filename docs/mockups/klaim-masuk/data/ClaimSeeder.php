<?php

namespace Database\Seeders;

use App\Models\Claim;
use Illuminate\Database\Seeder;

class ClaimSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $claims = [
            [
                'food_name' => 'Nasi Ayam Box',
                'food_icon' => 'box',
                'portions' => 40,
                'recipient_name' => 'Panti Asuhan Kasih',
                'recipient_city' => 'Jakarta Barat',
                'method' => Claim::METHOD_AMBIL_SENDIRI,
                'claim_date' => '2026-11-02',
                'status' => Claim::STATUS_MENUNGGU,
            ],
            [
                'food_name' => 'Roti Assorted',
                'food_icon' => 'bread',
                'portions' => 25,
                'recipient_name' => 'Dapur Harapan',
                'recipient_city' => 'Jakarta Selatan',
                'method' => Claim::METHOD_KURIR_ONLINE,
                'claim_date' => '2026-11-02',
                'status' => Claim::STATUS_MENUNGGU,
            ],
            [
                'food_name' => 'Paket Makan',
                'food_icon' => 'meal',
                'portions' => 30,
                'recipient_name' => 'Panti Al-Ikhlas',
                'recipient_city' => 'Bekasi',
                'method' => Claim::METHOD_AMBIL_SENDIRI,
                'claim_date' => '2026-11-01',
                'status' => Claim::STATUS_DISETUJUI,
            ],
            [
                'food_name' => 'Sayur Capcay',
                'food_icon' => 'bowl',
                'portions' => 15,
                'recipient_name' => 'Panti Asih',
                'recipient_city' => 'Jakarta Timur',
                'method' => Claim::METHOD_KURIR_ONLINE,
                'claim_date' => '2026-11-01',
                'status' => Claim::STATUS_DISETUJUI,
            ],
            [
                'food_name' => 'Nasi Kotak',
                'food_icon' => 'box',
                'portions' => 20,
                'recipient_name' => 'Panti Harapan',
                'recipient_city' => 'Jakarta Pusat',
                'method' => Claim::METHOD_AMBIL_SENDIRI,
                'claim_date' => '2026-10-31',
                'status' => Claim::STATUS_SELESAI,
            ],
            [
                'food_name' => 'Roti Tawar',
                'food_icon' => 'bread',
                'portions' => 10,
                'recipient_name' => 'Panti Kasih',
                'recipient_city' => 'Jakarta Barat',
                'method' => Claim::METHOD_AMBIL_SENDIRI,
                'claim_date' => '2026-10-30',
                'status' => Claim::STATUS_DITOLAK,
            ],
        ];

        foreach ($claims as $claim) {
            Claim::updateOrCreate(
                [
                    'food_name' => $claim['food_name'],
                    'recipient_name' => $claim['recipient_name'],
                ],
                $claim,
            );
        }
    }
}
