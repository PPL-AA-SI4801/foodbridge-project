<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\DonorProfile;
use App\Models\RecipientProfile;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedAdmin();
        $this->seedDonors();
        $this->seedRecipients();
    }

    private function seedAdmin(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@foodbridge.test'],
            [
                'nama' => 'Administrator Demo',
                'password' => 'password',
                'no_hp' => '081200000003',
                'role' => Role::Administrator,
                'status_akun' => AccountStatus::Aktif,
            ]
        );
    }

    private function seedDonors(): void
    {
        $donors = [
            [
                'email' => 'donor@foodbridge.test',
                'nama' => 'Donor Demo',
                'no_hp' => '081200000001',
                'status' => AccountStatus::Aktif,
                'profile' => [
                    'nama_usaha' => 'Toko Donor Demo',
                    'jenis_usaha' => 'Restoran',
                    'alamat' => 'Jl. Demo No. 1, Jakarta',
                    'lat' => -6.2000000,
                    'lng' => 106.8000000,
                ],
            ],
            [
                'email' => 'donor2@foodbridge.test',
                'nama' => 'Budi Santoso',
                'no_hp' => '081200000004',
                'status' => AccountStatus::Aktif,
                'profile' => [
                    'nama_usaha' => 'Warung Berkah',
                    'jenis_usaha' => 'Warung Makan',
                    'alamat' => 'Jl. Melati No. 12, Jakarta',
                    'lat' => -6.2100000,
                    'lng' => 106.8100000,
                ],
            ],
            [
                'email' => 'donor3@foodbridge.test',
                'nama' => 'Siti Aminah',
                'no_hp' => '081200000005',
                'status' => AccountStatus::Aktif,
                'profile' => [
                    'nama_usaha' => 'Dapur Ibu',
                    'jenis_usaha' => 'Catering',
                    'alamat' => 'Jl. Mawar No. 7, Jakarta',
                    'lat' => -6.2200000,
                    'lng' => 106.8200000,
                ],
            ],
            [
                'email' => 'donor-pending@foodbridge.test',
                'nama' => 'Donor Pending',
                'no_hp' => '081200000006',
                'status' => AccountStatus::Menunggu,
                'profile' => [
                    'nama_usaha' => 'Usaha Pending',
                    'jenis_usaha' => 'Restoran',
                    'alamat' => 'Jl. Pending No. 1, Jakarta',
                    'lat' => -6.2300000,
                    'lng' => 106.8300000,
                ],
            ],
        ];

        foreach ($donors as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'nama' => $data['nama'],
                    'password' => 'password',
                    'no_hp' => $data['no_hp'],
                    'role' => Role::Donor,
                    'status_akun' => $data['status'],
                ]
            );

            DonorProfile::updateOrCreate(
                ['user_id' => $user->id],
                $data['profile'] + ['user_id' => $user->id]
            );
        }
    }

    private function seedRecipients(): void
    {
        $recipients = [
            [
                'email' => 'penerima@foodbridge.test',
                'nama' => 'Penerima Demo',
                'no_hp' => '081200000002',
                'status' => AccountStatus::Aktif,
                'profile' => [
                    'nama_lembaga' => 'Panti Penerima Demo',
                    'jenis_lembaga' => 'Panti asuhan',
                    'alamat' => 'Jl. Demo No. 2, Jakarta',
                    'lat' => -6.2000000,
                    'lng' => 106.8000000,
                ],
            ],
            [
                'email' => 'penerima2@foodbridge.test',
                'nama' => 'Andi Wijaya',
                'no_hp' => '081200000007',
                'status' => AccountStatus::Aktif,
                'profile' => [
                    'nama_lembaga' => 'Yayasan Harapan',
                    'jenis_lembaga' => 'Yayasan',
                    'alamat' => 'Jl. Kenanga No. 5, Jakarta',
                    'lat' => -6.2400000,
                    'lng' => 106.8400000,
                ],
            ],
            [
                'email' => 'penerima3@foodbridge.test',
                'nama' => 'Dewi Lestari',
                'no_hp' => '081200000008',
                'status' => AccountStatus::Aktif,
                'profile' => [
                    'nama_lembaga' => 'Komunitas Peduli',
                    'jenis_lembaga' => 'Komunitas',
                    'alamat' => 'Jl. Anggrek No. 9, Jakarta',
                    'lat' => -6.2500000,
                    'lng' => 106.8500000,
                ],
            ],
            [
                'email' => 'penerima-pending@foodbridge.test',
                'nama' => 'Penerima Pending',
                'no_hp' => '081200000009',
                'status' => AccountStatus::Menunggu,
                'profile' => [
                    'nama_lembaga' => 'Lembaga Pending',
                    'jenis_lembaga' => 'Panti asuhan',
                    'alamat' => 'Jl. Pending No. 2, Jakarta',
                    'lat' => -6.2600000,
                    'lng' => 106.8600000,
                ],
            ],
        ];

        foreach ($recipients as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'nama' => $data['nama'],
                    'password' => 'password',
                    'no_hp' => $data['no_hp'],
                    'role' => Role::Penerima,
                    'status_akun' => $data['status'],
                ]
            );

            RecipientProfile::updateOrCreate(
                ['user_id' => $user->id],
                $data['profile'] + ['user_id' => $user->id]
            );
        }
    }
}
