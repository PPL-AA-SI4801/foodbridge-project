<?php

namespace Tests\Browser;

use App\Enums\AccountStatus;
use App\Enums\FoodPostStatus;
use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Models\Claim;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ClaimManagementTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Skenario Pengujian E2E:
     * - PBI-07: Klaim Berkuota oleh Penerima (kuota harian & validasi batas porsi)
     * - PBI-09: Persetujuan Klaim oleh Donor (perubahan status & pemotongan stok)
     */
    public function testClaimAndApprovalWorkflow(): void
    {
        // 1. Siapkan Kategori Makanan
        $category = FoodCategory::create([
            'nama_kategori' => 'Makanan Siap Saji',
            'slug' => 'makanan-siap-saji',
        ]);

        // 2. Siapkan Akun Donor Terverifikasi
        $donor = User::factory()->create([
            'nama' => 'Dapur Donor Test',
            'email' => 'donor.dusk@foodbridge.test',
            'password' => Hash::make('password'),
            'role' => Role::Donor,
            'status_akun' => AccountStatus::Aktif,
        ]);

        $donor->donorProfile()->create([
            'nama_usaha' => 'Dapur Donor Test',
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor No. 1, Jakarta',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        // 3. Siapkan Akun Penerima Terverifikasi
        $recipient = User::factory()->create([
            'nama' => 'Panti Penerima Test',
            'email' => 'penerima.dusk@foodbridge.test',
            'password' => Hash::make('password'),
            'role' => Role::Penerima,
            'status_akun' => AccountStatus::Aktif,
        ]);

        $recipient->recipientProfile()->create([
            'nama_lembaga' => 'Panti Penerima Test',
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => 'Jl. Penerima No. 2, Jakarta',
            'lat' => -6.21,
            'lng' => 106.81,
        ]);

        // TODO: implement the full Dusk browser flow (login, create food post, claim, approve, etc.)
        $this->assertTrue(true);
    }
}