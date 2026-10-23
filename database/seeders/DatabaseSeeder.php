<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $donor = User::create([
            'nama' => 'Donor Demo',
            'email' => 'donor@foodbridge.test',
            'password' => 'password',
            'no_hp' => '081200000001',
            'role' => Role::Donor,
            'status_akun' => AccountStatus::Aktif,
        ]);

        $donor->donorProfile()->create([
            'nama_usaha' => 'Toko Donor Demo',
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Demo No. 1, Jakarta',
        ]);

        $recipient = User::create([
            'nama' => 'Penerima Demo',
            'email' => 'penerima@foodbridge.test',
            'password' => 'password',
            'no_hp' => '081200000002',
            'role' => Role::Penerima,
            'status_akun' => AccountStatus::Aktif,
        ]);

        $recipient->recipientProfile()->create([
            'nama_lembaga' => 'Panti Penerima Demo',
            'jenis_lembaga' => 'Panti asuhan',
            'alamat' => 'Jl. Demo No. 2, Jakarta',
        ]);

        User::create([
            'nama' => 'Administrator Demo',
            'email' => 'admin@foodbridge.test',
            'password' => 'password',
            'no_hp' => '081200000003',
            'role' => Role::Administrator,
            'status_akun' => AccountStatus::Aktif,
        ]);
    }
}
