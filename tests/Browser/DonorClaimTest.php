<?php

namespace Tests\Browser;

use App\Enums\AccountStatus;
use App\Enums\FoodPostStatus;
use App\Enums\NotificationType;
use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Models\Claim;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DonorClaimTest extends DuskTestCase
{
    private static int $seq = 0;

    /**
     * Menghasilkan email unik untuk setiap eksekusi pengujian
     * agar data tidak bertabrakan pada database persistent.
     */
    private function uniqueEmail(string $prefix): string
    {
        self::$seq++;

        return sprintf('%s-%d-%d-%s@foodbridge.test', strtolower($prefix), time(), self::$seq, strtolower(Str::random(4)));
    }

    /**
     * Menghasilkan nama unik untuk mencegah duplikasi kolom unik (seperti nama_usaha).
     */
    private function uniqueName(string $base): string
    {
        self::$seq++;

        return sprintf('%s %d-%s', $base, time(), strtolower(Str::random(4)));
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function registerAndLoginDonor(Browser $browser, ?string $email = null, ?string $namaUsaha = null): array
    {
        $email = $email ?? $this->uniqueEmail('donor');
        $namaUsaha = $namaUsaha ?? $this->uniqueName('Dapur Donor Test');
        $password = 'Password123!';
        $fixture = base_path('tests/Browser/fixtures/legalitas.pdf');

        $browser->logout();

        // 1. Registrasi Akun Donor Baru via UI
        $browser->visit('/register')
            ->assertSee('Buat akun')
            ->type('@register-nama', $namaUsaha)
            ->type('@register-email', $email)
            ->type('@register-no-hp', '081234567890')
            ->type('@register-password', $password)
            ->type('@register-password-confirmation', $password)
            ->press('@register-next')
            ->pause(500)
            ->assertVisible('@register-nama-usaha')
            ->type('@register-nama-usaha', $namaUsaha)
            ->type('@register-jenis-usaha', 'Restoran')
            ->type('@register-alamat', 'Jl. Donor No. 1, Jakarta')
            ->select('@register-jenis-dokumen', 'nib')
            ->attach('dokumen', $fixture)
            ->press('@register-submit')
            ->waitForText('Status verifikasi akun')
            ->assertPathIs('/login');

        // 2. Verifikasi dan aktifkan akun di DB agar dapat masuk ke dashboard
        $donorUser = User::where('email', $email)->firstOrFail();
        $donorUser->update(['status_akun' => AccountStatus::Aktif]);
        $donorUser->verificationDocuments()->where('status', VerificationStatus::Menunggu->value)->update([
            'status' => VerificationStatus::Disetujui->value,
            'catatan' => null,
            'verified_at' => now(),
        ]);
        $donorProfile = $donorUser->donorProfile;

        // 3. Login Akun Donor Baru via form UI
        $browser->type('@login-email', $email)
            ->type('@login-password', $password)
            ->press('@login-submit')
            ->waitForLocation('/dashboard/donor')
            ->assertPathIs('/dashboard/donor');

        return [$donorUser, $donorProfile];
    }

    private function createDonor(?string $email = null, ?string $namaUsaha = null): array
    {
        $email = $email ?? $this->uniqueEmail('donor');
        $namaUsaha = $namaUsaha ?? $this->uniqueName('Dapur Donor Test');

        $user = User::factory()->create([
            'nama' => $namaUsaha,
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => Role::Donor,
            'status_akun' => AccountStatus::Aktif,
        ]);

        $user->verificationDocuments()->create([
            'jenis_dokumen' => 'nib',
            'file_path' => 'verification-documents/'.$user->id.'/legalitas.pdf',
            'status' => VerificationStatus::Disetujui->value,
            'verified_at' => now(),
        ]);

        $profile = $user->donorProfile()->create([
            'nama_usaha' => $namaUsaha,
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor No. 1, Jakarta',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        return [$user, $profile];
    }

    private function createRecipient(?string $email = null, ?string $namaLembaga = null): array
    {
        $email = $email ?? $this->uniqueEmail('penerima');
        $namaLembaga = $namaLembaga ?? $this->uniqueName('Panti Penerima Test');

        $user = User::factory()->create([
            'nama' => $namaLembaga,
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => Role::Penerima,
            'status_akun' => AccountStatus::Aktif,
        ]);

        $user->verificationDocuments()->create([
            'jenis_dokumen' => 'nib',
            'file_path' => 'verification-documents/'.$user->id.'/legalitas.pdf',
            'status' => VerificationStatus::Disetujui->value,
            'verified_at' => now(),
        ]);

        $profile = $user->recipientProfile()->create([
            'nama_lembaga' => $namaLembaga,
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => 'Jl. Penerima No. 2, Jakarta',
            'lat' => -6.21,
            'lng' => 106.81,
        ]);

        return [$user, $profile];
    }

    private function createFoodPost($donorProfile, $category, array $overrides = []): FoodPost
    {
        return FoodPost::create(array_merge([
            'judul' => 'Nasi Kotak Donasi ' . Str::random(6),
            'deskripsi' => 'Makanan untuk uji persetujuan',
            'foto' => 'food-posts/sample.jpg',
            'jumlah_porsi' => 20,
            'sisa_porsi' => 20,
            'alamat' => 'Jl. Donor No. 1, Jakarta',
            'lat' => -6.2,
            'lng' => 106.8,
            'batas_konsumsi' => now()->addHours(6),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donorProfile->id,
            'category_id' => $category->id,
        ], $overrides));
    }

    private function createClaim($post, $recipientProfile, int $qty, string $status = 'pending'): Claim
    {
        return Claim::create([
            'post_id' => $post->id,
            'recipient_id' => $recipientProfile->id,
            'jumlah_porsi' => $qty,
            'status' => $status,
            'tanggal_klaim' => now()->toDateString(),
        ]);
    }

    // ---------------------------------------------------------------------
    // TC-09-01 : Daftar hanya klaim pending milik Donor
    // ---------------------------------------------------------------------
    public function test_tc_09_01_daftar_hanya_klaim_pending_milik_donor(): void
    {
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Siap Saji']);

        [$donorBUser, $donorBProfile] = $this->createDonor();
        [$recipientUser, $recipientProfile] = $this->createRecipient();

        $this->browse(function (Browser $browser) use ($category, $donorBProfile, $recipientProfile) {
            // Diawali dengan registrasi donor baru dan login via UI
            [$donorAUser, $donorAProfile] = $this->registerAndLoginDonor($browser);

            $postA = $this->createFoodPost($donorAProfile, $category, ['judul' => 'Makanan Donor A ' . Str::random(5)]);
            $postB = $this->createFoodPost($donorBProfile, $category, ['judul' => 'Makanan Donor B ' . Str::random(5)]);

            $this->createClaim($postA, $recipientProfile, 3, 'pending');
            $this->createClaim($postB, $recipientProfile, 2, 'pending');

            $browser->visit('/donor/claims')
                ->assertSee('Kelola Klaim Masuk')
                ->assertSee($postA->judul)
                ->assertDontSee($postB->judul);
        });
    }

    // ---------------------------------------------------------------------
    // TC-09-02 : Setujui klaim
    // ---------------------------------------------------------------------
    public function test_tc_09_02_setujui_klaim(): void
    {
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Siap Saji']);
        [$donorUser, $donorProfile] = $this->createDonor();
        [$recipientUser, $recipientProfile] = $this->createRecipient();

        $foodPost = $this->createFoodPost($donorProfile, $category, ['sisa_porsi' => 20, 'jumlah_porsi' => 20]);
        $claim = $this->createClaim($foodPost, $recipientProfile, 5, 'pending');

        $this->browse(function (Browser $browser) use ($donorUser, $foodPost, $recipientProfile, $claim) {
            $browser->loginAs($donorUser)
                ->visit('/donor/claims')
                ->assertSee('Kelola Klaim Masuk')
                ->assertSee($foodPost->judul)
                ->assertSee($recipientProfile->nama_lembaga)
                ->assertSee('5 porsi')
                ->press('Setujui')
                ->waitForText('Klaim berhasil disetujui dan sisa porsi telah diperbarui.')
                ->assertSee('Klaim berhasil disetujui dan sisa porsi telah diperbarui.');

            $claim->refresh();
            $this->assertSame('approved', $claim->status);
            $this->assertNotNull($claim->approved_at);
            $this->assertSame(15, (int) FoodPost::find($foodPost->id)->sisa_porsi);
        });

        $this->assertDatabaseHas('notifications', [
            'user_id' => $recipientUser->id,
            'tipe' => NotificationType::Claim->value,
        ]);
    }

    // ---------------------------------------------------------------------
    // TC-09-03 : Tolak klaim
    // ---------------------------------------------------------------------
    public function test_tc_09_03_tolak_klaim(): void
    {
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Siap Saji']);
        [$donorUser, $donorProfile] = $this->createDonor();
        [$recipientUser, $recipientProfile] = $this->createRecipient();

        $foodPost = $this->createFoodPost($donorProfile, $category, ['sisa_porsi' => 20, 'jumlah_porsi' => 20]);
        $claim = $this->createClaim($foodPost, $recipientProfile, 5, 'pending');

        $this->browse(function (Browser $browser) use ($donorUser, $foodPost, $recipientProfile, $claim) {
            $browser->loginAs($donorUser)
                ->visit('/donor/claims')
                ->assertSee('Kelola Klaim Masuk')
                ->assertSee($foodPost->judul)
                ->assertSee($recipientProfile->nama_lembaga);

            // Menerima dialog konfirmasi browser (klik OK / terima dialog)
            $browser->script('window.confirm = () => true;');

            $browser->press('Tolak')
                ->waitForText('Klaim berhasil ditolak.')
                ->assertSee('Klaim berhasil ditolak.');

            $claim->refresh();
            $this->assertSame('rejected', $claim->status);
            $this->assertSame(20, (int) FoodPost::find($foodPost->id)->sisa_porsi);
        });

        $this->assertDatabaseHas('notifications', [
            'user_id' => $recipientUser->id,
            'tipe' => NotificationType::Claim->value,
        ]);
    }

    // ---------------------------------------------------------------------
    // TC-09-04 : Proses klaim milik post Donor lain
    // ---------------------------------------------------------------------
    public function test_tc_09_04_proses_klaim_milik_post_donor_lain(): void
    {
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Siap Saji']);
        [$donorAUser, $donorAProfile] = $this->createDonor();
        [$donorBUser, $donorBProfile] = $this->createDonor();
        [$recipientUser, $recipientProfile] = $this->createRecipient();

        $postA = $this->createFoodPost($donorAProfile, $category, ['judul' => 'Makanan Donor A ' . Str::random(5)]);
        $claim = $this->createClaim($postA, $recipientProfile, 4, 'pending');

        $this->browse(function (Browser $browser) use ($donorBUser, $postA) {
            $browser->loginAs($donorBUser)
                ->visit('/donor/claims')
                ->assertSee('Kelola Klaim Masuk')
                ->assertDontSee($postA->judul);
        });

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->actingAs($donorBUser);

        $response = $this->patch(route('donor.claims.approve', $claim));
        $response->assertForbidden();

        $claim->refresh();
        $this->assertSame('pending', $claim->status);
    }

    // ---------------------------------------------------------------------
    // TC-09-05 : Klaim sudah diproses
    // ---------------------------------------------------------------------
    public function test_tc_09_05_klaim_sudah_diproses(): void
    {
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Siap Saji']);
        [$donorUser, $donorProfile] = $this->createDonor();
        [$recipientUser, $recipientProfile] = $this->createRecipient();

        // 1. Siapkan akun Donor, postingan makanan (sisa 20 porsi), dan klaim #1 (5 porsi)
        $foodPost = $this->createFoodPost($donorProfile, $category, ['sisa_porsi' => 20, 'jumlah_porsi' => 20]);
        $claim = $this->createClaim($foodPost, $recipientProfile, 5, 'pending');

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->actingAs($donorUser);

        // 2. Persetujuan pertama: klaim disetujui, sisa porsi berkurang 20 -> 15
        $first = $this->patch(route('donor.claims.approve', $claim));
        $first->assertRedirect();
        $claim->refresh();
        $this->assertSame('approved', $claim->status);
        $this->assertSame(15, (int) FoodPost::find($foodPost->id)->sisa_porsi);

        // Verifikasi UI: klaim muncul di tab disetujui dan tidak ada tombol setujui
        $this->browse(function (Browser $browser) use ($donorUser, $foodPost, $claim) {
            $browser->loginAs($donorUser)
                ->visit('/donor/claims?status=disetujui')
                ->assertSee('Kelola Klaim Masuk')
                ->assertSee($foodPost->judul)
                ->assertDontSee('@approve-claim-'.$claim->id);
        });

        // 3-5. Kirim kembali request persetujuan kedua untuk klaim yang sama -> ditolak
        $second = $this->patch(route('donor.claims.approve', $claim));
        $second->assertRedirect();
        $second->assertSessionHas('error', 'Klaim ini sudah diproses sebelumnya.');

        // 6. Verifikasi database: status tetap approved dan sisa porsi tidak berkurang dua kali (tetap 15)
        $claim->refresh();
        $this->assertSame('approved', $claim->status);
        $this->assertSame(15, (int) FoodPost::find($foodPost->id)->sisa_porsi);
    }

    // ---------------------------------------------------------------------
    // TC-09-06 : Sisa porsi tidak cukup
    // ---------------------------------------------------------------------
    public function test_tc_09_06_sisa_porsi_tidak_cukup(): void
    {
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Siap Saji']);
        [$donorUser, $donorProfile] = $this->createDonor();
        [$recipientUser, $recipientProfile] = $this->createRecipient();

        $foodPost = $this->createFoodPost($donorProfile, $category, ['sisa_porsi' => 3, 'jumlah_porsi' => 10]);
        $claim = $this->createClaim($foodPost, $recipientProfile, 5, 'pending');

        $this->browse(function (Browser $browser) use ($donorUser, $foodPost, $claim) {
            $browser->loginAs($donorUser)
                ->visit('/donor/claims')
                ->assertSee('Kelola Klaim Masuk')
                ->assertSee($foodPost->judul)
                ->assertSee('Melebihi sisa porsi')
                ->press('@approve-claim-'.$claim->id)
                ->waitForText('Sisa porsi makanan tidak mencukupi untuk klaim ini.')
                ->assertSee('Sisa porsi makanan tidak mencukupi untuk klaim ini.');
        });

        $claim->refresh();
        $this->assertSame('pending', $claim->status);
        $this->assertSame(3, (int) FoodPost::find($foodPost->id)->sisa_porsi);
    }

    // ---------------------------------------------------------------------
    // TC-09-07 : Makanan sudah kedaluwarsa
    // ---------------------------------------------------------------------
    public function test_tc_09_07_makanan_sudah_kedaluwarsa(): void
    {
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Siap Saji']);
        [$donorUser, $donorProfile] = $this->createDonor();
        [$recipientUser, $recipientProfile] = $this->createRecipient();

        // 1. Ubah batas_konsumsi ke masa lalu
        $foodPost = $this->createFoodPost($donorProfile, $category, [
            'batas_konsumsi' => now()->subHour(),
            'sisa_porsi' => 20,
        ]);
        $claim = $this->createClaim($foodPost, $recipientProfile, 2, 'pending');

        // 2. Login Donor dan klik Setujui pada UI browser -> Gagal, pesan "Makanan sudah kedaluwarsa."
        $this->browse(function (Browser $browser) use ($donorUser, $foodPost, $claim) {
            $browser->loginAs($donorUser)
                ->visit('/donor/claims')
                ->assertSee('Kelola Klaim Masuk')
                ->assertSee($foodPost->judul)
                ->press('@approve-claim-'.$claim->id)
                ->waitForText('Makanan sudah kedaluwarsa.')
                ->assertSee('Makanan sudah kedaluwarsa.');
        });

        // 3. Verifikasi database: status klaim tetap pending
        $claim->refresh();
        $this->assertSame('pending', $claim->status);
    }

    // ---------------------------------------------------------------------
    // TC-09-08 : Peran bukan Donor
    // ---------------------------------------------------------------------
    public function test_tc_09_08_peran_bukan_donor(): void
    {
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Siap Saji']);
        [$donorUser, $donorProfile] = $this->createDonor();
        [$recipientUser, $recipientProfile] = $this->createRecipient();

        $foodPost = $this->createFoodPost($donorProfile, $category);
        $claim = $this->createClaim($foodPost, $recipientProfile, 2, 'pending');

        $this->browse(function (Browser $browser) use ($recipientUser) {
            // Aktor Penerima mencoba akses halaman kelola klaim donor via browser UI -> 403 Forbidden
            $browser->loginAs($recipientUser)
                ->visit('/donor/claims')
                ->assertSee('403');
        });

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

        // 1. Penerima kirim request ke endpoint setujui -> 403 Forbidden
        $this->actingAs($recipientUser);
        $responseRecipient = $this->patch(route('donor.claims.approve', $claim));
        $responseRecipient->assertForbidden();

        // 2. Admin kirim request ke endpoint setujui -> 403 Forbidden
        $adminUser = User::factory()->create([
            'nama' => 'Admin Test',
            'email' => $this->uniqueEmail('admin'),
            'password' => Hash::make('password'),
            'role' => Role::Administrator,
            'status_akun' => AccountStatus::Aktif,
        ]);
        $this->actingAs($adminUser);
        $responseAdmin = $this->patch(route('donor.claims.approve', $claim));
        $responseAdmin->assertForbidden();

        $claim->refresh();
        $this->assertSame('pending', $claim->status);
    }

    // ---------------------------------------------------------------------
    // TC-09-09 : Klaim tidak ditemukan
    // ---------------------------------------------------------------------
    public function test_tc_09_09_klaim_tidak_ditemukan(): void
    {
        [$donorUser, $donorProfile] = $this->createDonor();

        $this->browse(function (Browser $browser) use ($donorUser) {
            $browser->loginAs($donorUser)
                ->visit('/donor/claims?claim=999999')
                ->assertSee('Kelola Klaim Masuk');
        });

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->actingAs($donorUser);

        $response = $this->patch('/donor/claims/999999/approve');
        $response->assertNotFound();
    }

    // ---------------------------------------------------------------------
    // TC-09-10 : Dua persetujuan bersamaan
    // ---------------------------------------------------------------------
    public function test_tc_09_10_dua_persetujuan_bersamaan(): void
    {
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Siap Saji']);
        [$donorUser, $donorProfile] = $this->createDonor();
        [$recipientUser, $recipientProfile] = $this->createRecipient();

        $foodPost = $this->createFoodPost($donorProfile, $category, ['sisa_porsi' => 10, 'jumlah_porsi' => 10]);
        $claim1 = $this->createClaim($foodPost, $recipientProfile, 6, 'pending');
        $claim2 = $this->createClaim($foodPost, $recipientProfile, 6, 'pending');

        $this->browse(function (Browser $browser) use ($donorUser, $foodPost) {
            $browser->loginAs($donorUser)
                ->visit('/donor/claims')
                ->assertSee('Kelola Klaim Masuk')
                ->assertSee($foodPost->judul);
        });

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->actingAs($donorUser);

        $first = $this->patch(route('donor.claims.approve', $claim1));
        $first->assertRedirect();
        $this->assertSame(4, (int) FoodPost::find($foodPost->id)->sisa_porsi);

        $second = $this->patch(route('donor.claims.approve', $claim2));
        $second->assertRedirect();
        $second->assertSessionHas('error');

        $this->assertSame(4, (int) FoodPost::find($foodPost->id)->sisa_porsi);
        $claim2->refresh();
        $this->assertSame('pending', $claim2->status);
    }
}