<?php

namespace Tests\Browser;

use App\Enums\AccountStatus;
use App\Enums\FoodPostStatus;
use App\Enums\Role;
use App\Models\Claim;
use App\Models\DonorProfile;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\RecipientProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DonorClaimTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function testDonorCanApproveAndRejectAClaim(): void
    {
        // --- Data dasar ---
        $category = FoodCategory::create([
            'nama_kategori' => 'Makanan Siap Saji',
            'slug' => 'makanan-siap-saji',
        ]);

        $donorUser = User::factory()->create([
            'nama' => 'Dapur Donor Test',
            'email' => 'donor.dusk@foodbridge.test',
            'password' => Hash::make('password'),
            'role' => Role::Donor,
            'status_akun' => AccountStatus::Aktif,
        ]);
        $donorProfile = $donorUser->donorProfile()->create([
            'nama_usaha' => 'Dapur Donor Test',
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor No. 1, Jakarta',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        $recipientUser = User::factory()->create([
            'nama' => 'Panti Penerima Test',
            'email' => 'penerima.dusk@foodbridge.test',
            'password' => Hash::make('password'),
            'role' => Role::Penerima,
            'status_akun' => AccountStatus::Aktif,
        ]);
        $recipientProfile = $recipientUser->recipientProfile()->create([
            'nama_lembaga' => 'Panti Penerima Test',
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => 'Jl. Penerima No. 2, Jakarta',
            'lat' => -6.21,
            'lng' => 106.81,
        ]);

        // --- Klaim yang akan DISETUJUI ---
        $postToApprove = FoodPost::create([
            'judul' => 'Nasi Kotak Setuju',
            'deskripsi' => 'Makanan untuk disetujui',
            'foto' => 'food-posts/sample.jpg',
            'jumlah_porsi' => 10,
            'sisa_porsi' => 10,
            'alamat' => 'Jl. Donor No. 1, Jakarta',
            'lat' => -6.2,
            'lng' => 106.8,
            'batas_konsumsi' => now()->addHours(6),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donorProfile->id,
            'category_id' => $category->id,
        ]);

        $claimToApprove = Claim::create([
            'post_id' => $postToApprove->id,
            'recipient_id' => $recipientProfile->id,
            'jumlah_porsi' => 4,
            'status' => 'pending',
            'tanggal_klaim' => Carbon::today(),
        ]);

        // 1. Donor menyetujui klaim
        $this->browse(function (Browser $browser) use ($donorUser, $postToApprove, $recipientProfile) {
            $browser->loginAs($donorUser)
                ->visit('/donor/claims')
                ->assertSee('Kelola Klaim Masuk')
                ->assertSee($postToApprove->judul)
                ->assertSee($recipientProfile->nama_lembaga)
                ->assertSee('4 porsi')
                ->press('Setujui')
                ->waitForText('Klaim berhasil disetujui dan sisa porsi telah diperbarui.')
                ->assertSee('Klaim berhasil disetujui dan sisa porsi telah diperbarui.');
        });

        $this->assertDatabaseHas('claims', [
            'id' => $claimToApprove->id,
            'status' => 'approved',
        ]);
        $this->assertNotNull(Claim::find($claimToApprove->id)->approved_at);
        $this->assertSame(6, (int) FoodPost::find($postToApprove->id)->sisa_porsi);

        // --- Klaim yang akan DITOLAK ---
        $postToReject = FoodPost::create([
            'judul' => 'Roti Tolak',
            'deskripsi' => 'Makanan untuk ditolak',
            'foto' => 'food-posts/sample.jpg',
            'jumlah_porsi' => 10,
            'sisa_porsi' => 10,
            'alamat' => 'Jl. Donor No. 1, Jakarta',
            'lat' => -6.2,
            'lng' => 106.8,
            'batas_konsumsi' => now()->addHours(6),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donorProfile->id,
            'category_id' => $category->id,
        ]);

        $claimToReject = Claim::create([
            'post_id' => $postToReject->id,
            'recipient_id' => $recipientProfile->id,
            'jumlah_porsi' => 2,
            'status' => 'pending',
            'tanggal_klaim' => Carbon::today(),
        ]);

        // 2. Donor menolak klaim
        $this->browse(function (Browser $browser) use ($donorUser, $postToReject, $recipientProfile) {
            $browser->loginAs($donorUser)
                ->visit('/donor/claims')
                ->assertSee('Kelola Klaim Masuk')
                ->assertSee($postToReject->judul)
                ->assertSee($recipientProfile->nama_lembaga)
                ->press('Tolak')
                ->acceptDialog()
                ->waitForText('Klaim berhasil ditolak.')
                ->assertSee('Klaim berhasil ditolak.');
        });

        $this->assertDatabaseHas('claims', [
            'id' => $claimToReject->id,
            'status' => 'rejected',
        ]);
        // Stok tidak berubah saat ditolak
        $this->assertSame(10, (int) FoodPost::find($postToReject->id)->sisa_porsi);
    }
}