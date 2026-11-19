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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ClaimManagementTest extends DuskTestCase
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

    private function registerAndLoginRecipient(Browser $browser, ?string $email = null, ?string $namaLembaga = null): array
    {
        $email = $email ?? $this->uniqueEmail('penerima');
        $namaLembaga = $namaLembaga ?? $this->uniqueName('Panti Penerima Test');
        $password = 'Password123!';
        $fixture = base_path('tests/Browser/fixtures/legalitas.pdf');

        $browser->logout();

        // 1. Registrasi Akun Penerima Baru via UI
        $browser->visit('/register')
            ->assertSee('Buat akun');
        $browser->script("document.querySelector('input[name=\"role\"][value=\"penerima\"]').click();");
        $browser->type('@register-nama', $namaLembaga)
            ->type('@register-email', $email)
            ->type('@register-no-hp', '081234567891')
            ->type('@register-password', $password)
            ->type('@register-password-confirmation', $password)
            ->press('@register-next')
            ->pause(500)
            ->assertVisible('@register-nama-lembaga')
            ->type('@register-nama-lembaga', $namaLembaga)
            ->type('@register-jenis-lembaga', 'Panti Asuhan')
            ->type('@register-alamat', 'Jl. Penerima No. 2, Jakarta')
            ->select('@register-jenis-dokumen', 'nib')
            ->attach('dokumen', $fixture)
            ->press('@register-submit')
            ->waitForText('Status verifikasi akun')
            ->assertPathIs('/login');

        // 2. Verifikasi dan aktifkan akun di DB agar dapat masuk ke dashboard dan katalog
        $user = User::where('email', $email)->firstOrFail();
        $user->update(['status_akun' => AccountStatus::Aktif]);
        $user->verificationDocuments()->where('status', VerificationStatus::Menunggu->value)->update([
            'status' => VerificationStatus::Disetujui->value,
            'catatan' => null,
            'verified_at' => now(),
        ]);
        $recipientProfile = $user->recipientProfile;

        // 3. Login Akun Baru via form UI
        $browser->type('@login-email', $email)
            ->type('@login-password', $password)
            ->press('@login-submit')
            ->waitForLocation('/dashboard/penerima')
            ->assertPathIs('/dashboard/penerima');

        return [$user, $recipientProfile];
    }

    private function createVerifiedDonor(?string $email = null, ?string $namaUsaha = null): array
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

    private function createVerifiedRecipient(?string $email = null, ?string $namaLembaga = null): array
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
            'deskripsi' => 'Makanan untuk uji klaim',
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

    private function claimViaUi(Browser $browser, FoodPost $foodPost, int $qty): void
    {
        $browser->visit('/penerima/katalog?q='.urlencode($foodPost->judul))
            ->waitForText($foodPost->judul)
            ->assertSee($foodPost->judul)
            ->click('button[aria-controls="claim-form-'.$foodPost->id.'"]')
            ->waitFor('#claim-form-'.$foodPost->id)
            ->type('#jumlah_porsi_'.$foodPost->id, (string) $qty);
    }

    // ---------------------------------------------------------------------
    // TC-07-01 : Klaim valid
    // ---------------------------------------------------------------------
    public function test_tc_07_01_klaim_valid(): void
    {
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Siap Saji']);
        [$donorUser, $donorProfile] = $this->createVerifiedDonor();
        $foodPost = $this->createFoodPost($donorProfile, $category, ['sisa_porsi' => 20, 'jumlah_porsi' => 20]);

        $this->browse(function (Browser $browser) use ($foodPost) {
            [$recipientUser, $recipientProfile] = $this->registerAndLoginRecipient($browser);

            $this->claimViaUi($browser, $foodPost, 5);
            $browser->press('Ajukan Klaim')
                ->waitForText('Klaim berhasil diajukan')
                ->assertPathIs('/recipient/claims')
                ->assertSee($foodPost->judul);

            $this->assertDatabaseHas('claims', [
                'post_id' => $foodPost->id,
                'recipient_id' => $recipientProfile->id,
                'jumlah_porsi' => 5,
                'status' => 'pending',
            ]);
        });
    }

    // ---------------------------------------------------------------------
    // TC-07-02 : Klaim sebesar sisa porsi
    // ---------------------------------------------------------------------
    public function test_tc_07_02_klaim_sebesar_sisa_porsi(): void
    {
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Siap Saji']);
        [$donorUser, $donorProfile] = $this->createVerifiedDonor();
        $foodPost = $this->createFoodPost($donorProfile, $category, ['sisa_porsi' => 20, 'jumlah_porsi' => 20]);

        $this->browse(function (Browser $browser) use ($foodPost) {
            [$recipientUser, $recipientProfile] = $this->createVerifiedRecipient();
            $browser->loginAs($recipientUser);

            $this->claimViaUi($browser, $foodPost, 20);
            $browser->press('Ajukan Klaim')
                ->waitForText('Klaim berhasil diajukan')
                ->assertPathIs('/recipient/claims');

            $this->assertDatabaseHas('claims', [
                'post_id' => $foodPost->id,
                'recipient_id' => $recipientProfile->id,
                'jumlah_porsi' => 20,
                'status' => 'pending',
            ]);
        });
    }

    // ---------------------------------------------------------------------
    // TC-07-03 : Klaim ke-3 (tepat di batas kuota)
    // ---------------------------------------------------------------------
    public function test_tc_07_03_klaim_ke_3_tepat_di_batas_kuota(): void
    {
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Siap Saji']);
        [$donorUser, $donorProfile] = $this->createVerifiedDonor();

        $this->browse(function (Browser $browser) use ($donorProfile, $category) {
            [$recipientUser, $recipientProfile] = $this->createVerifiedRecipient();
            $browser->loginAs($recipientUser);

            $tag = Str::random(5);
            for ($i = 0; $i < 2; $i++) {
                $post = $this->createFoodPost($donorProfile, $category, ['judul' => "Post Kuota {$i} {$tag}"]);
                Claim::create([
                    'post_id' => $post->id,
                    'recipient_id' => $recipientProfile->id,
                    'jumlah_porsi' => 1,
                    'status' => 'pending',
                    'tanggal_klaim' => now()->toDateString(),
                ]);
            }

            $foodPost = $this->createFoodPost($donorProfile, $category, ['judul' => "Post Klaim Ketiga {$tag}"]);

            $this->claimViaUi($browser, $foodPost, 3);
            $browser->press('Ajukan Klaim')
                ->waitForText('Klaim berhasil diajukan')
                ->visit('/recipient/claims')
                ->assertSee('Klaim Saya');

            $this->assertSame(3, Claim::where('recipient_id', $recipientProfile->id)
                ->whereDate('tanggal_klaim', now()->toDateString())
                ->where('status', '!=', 'rejected')
                ->count());
        });
    }

    // ---------------------------------------------------------------------
    // TC-07-04 : Klaim ke-4 melebihi kuota
    // ---------------------------------------------------------------------
    public function test_tc_07_04_klaim_ke_4_melebihi_kuota(): void
    {
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Siap Saji']);
        [$donorUser, $donorProfile] = $this->createVerifiedDonor();

        $this->browse(function (Browser $browser) use ($donorProfile, $category) {
            [$recipientUser, $recipientProfile] = $this->createVerifiedRecipient();
            $browser->loginAs($recipientUser);

            $tag = Str::random(5);
            for ($i = 0; $i < 3; $i++) {
                $post = $this->createFoodPost($donorProfile, $category, ['judul' => "Post Kuota {$i} {$tag}"]);
                Claim::create([
                    'post_id' => $post->id,
                    'recipient_id' => $recipientProfile->id,
                    'jumlah_porsi' => 1,
                    'status' => 'pending',
                    'tanggal_klaim' => now()->toDateString(),
                ]);
            }

            $foodPost = $this->createFoodPost($donorProfile, $category, ['judul' => "Post Klaim Keempat {$tag}"]);
            $before = Claim::where('recipient_id', $recipientProfile->id)->count();

            $this->claimViaUi($browser, $foodPost, 2);
            $browser->press('Ajukan Klaim')
                ->waitForText('Kuota klaim harian Anda sudah habis (3/3).')
                ->assertPathIs('/penerima/katalog');

            $this->assertSame($before, Claim::where('recipient_id', $recipientProfile->id)->count());
        });
    }

    // ---------------------------------------------------------------------
    // TC-07-05 : Porsi melebihi sisa
    // ---------------------------------------------------------------------
    public function test_tc_07_05_porsi_melebihi_sisa(): void
    {
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Siap Saji']);
        [$donorUser, $donorProfile] = $this->createVerifiedDonor();
        $foodPost = $this->createFoodPost($donorProfile, $category, ['sisa_porsi' => 10, 'jumlah_porsi' => 10]);

        $this->browse(function (Browser $browser) use ($foodPost) {
            [$recipientUser, $recipientProfile] = $this->createVerifiedRecipient();
            $browser->loginAs($recipientUser);

            $before = Claim::where('recipient_id', $recipientProfile->id)->count();

            $this->claimViaUi($browser, $foodPost, 25);
            $browser->script('document.getElementById("claim-form-'.$foodPost->id.'").submit();');
            $browser->waitForText('Jumlah porsi yang diklaim tidak boleh melebihi sisa porsi makanan.')
                ->assertPathIs('/penerima/katalog');

            $this->assertSame($before, Claim::where('recipient_id', $recipientProfile->id)->count());
        });
    }

    // ---------------------------------------------------------------------
    // TC-07-06 : Input porsi tidak valid
    // ---------------------------------------------------------------------
    public function test_tc_07_06_input_porsi_tidak_valid(): void
    {
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Siap Saji']);
        [$donorUser, $donorProfile] = $this->createVerifiedDonor();
        [$recipientUser, $recipientProfile] = $this->createVerifiedRecipient();

        $foodPost = $this->createFoodPost($donorProfile, $category, ['sisa_porsi' => 10, 'jumlah_porsi' => 10]);

        $this->browse(function (Browser $browser) use ($recipientUser, $foodPost) {
            $browser->loginAs($recipientUser);
            $this->claimViaUi($browser, $foodPost, 1);

            // Coba submit nilai input tidak valid (0) menggunakan JavaScript di browser UI
            $browser->script('document.getElementById("jumlah_porsi_'.$foodPost->id.'").value = "0"; document.getElementById("claim-form-'.$foodPost->id.'").submit();');
            $browser->pause(500)
                ->assertPathIs('/penerima/katalog');
        });

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->actingAs($recipientUser);

        $beforeClaims = Claim::where('recipient_id', $recipientProfile->id)->count();

        foreach ([0, -2, 'abc'] as $value) {
            $response = $this->post(route('recipient.claims.store', $foodPost), [
                'jumlah_porsi' => $value,
            ]);
            $response->assertSessionHasErrors('jumlah_porsi');
        }

        $response = $this->post(route('recipient.claims.store', $foodPost), [
            'jumlah_porsi' => '',
        ]);
        $response->assertSessionHasErrors('jumlah_porsi');

        // Verifikasi database: tidak ada klaim yang masuk ke database untuk input tidak valid tersebut
        $this->assertSame($beforeClaims, Claim::where('recipient_id', $recipientProfile->id)->count());
    }

    // ---------------------------------------------------------------------
    // TC-07-07 : Makanan kedaluwarsa
    // ---------------------------------------------------------------------
    public function test_tc_07_07_makanan_kedaluwarsa(): void
    {
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Siap Saji']);
        [$donorUser, $donorProfile] = $this->createVerifiedDonor();
        [$recipientUser, $recipientProfile] = $this->createVerifiedRecipient();

        // 1. Siapkan akun Penerima terverifikasi dan postingan makanan yang telah melewati batas konsumsi
        $expiredTitle = 'Kedaluwarsa ' . Str::random(6);
        $foodPost = $this->createFoodPost($donorProfile, $category, [
            'judul' => $expiredTitle,
            'batas_konsumsi' => now()->subHour(),
            'sisa_porsi' => 20,
        ]);

        // Buka browser sesi Penerima
        $this->browse(function (Browser $browser) use ($recipientUser) {
            $browser->loginAs($recipientUser)
                ->visit('/penerima/katalog')
                ->assertSee('Katalog Peta Pangan');
        });

        // 2. Login sebagai Penerima, lalu kirimkan request klaim sebanyak 2 porsi
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->actingAs($recipientUser);

        // 3-4. Sistem menolak klaim dan mengembalikan pesan kesalahan: 'Makanan sudah kedaluwarsa dan tidak dapat diklaim.'
        $response = $this->post(route('recipient.claims.store', $foodPost), [
            'jumlah_porsi' => 2,
        ]);

        $response->assertSessionHasErrors([
            'jumlah_porsi' => 'Makanan sudah kedaluwarsa dan tidak dapat diklaim.',
        ]);

        // 5. Verifikasi database: record klaim tidak tersimpan
        $this->assertDatabaseMissing('claims', [
            'post_id' => $foodPost->id,
            'recipient_id' => $recipientProfile->id,
        ]);
    }

    // ---------------------------------------------------------------------
    // TC-07-08 : Makanan habis
    // ---------------------------------------------------------------------
    public function test_tc_07_08_makanan_habis(): void
    {
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Siap Saji']);
        [$donorUser, $donorProfile] = $this->createVerifiedDonor();
        [$recipientUser, $recipientProfile] = $this->createVerifiedRecipient();

        // 1. Siapkan postingan makanan yang berstatus habis (sisa_porsi = 0)
        $emptyTitle = 'Habis ' . Str::random(6);
        $foodPost = $this->createFoodPost($donorProfile, $category, [
            'judul' => $emptyTitle,
            'sisa_porsi' => 0,
            'jumlah_porsi' => 10,
        ]);

        // 2-3. Login Penerima via UI browser, buka katalog -> verifikasi makanan habis tidak ditampilkan (tombol klaim tidak tersedia)
        $this->browse(function (Browser $browser) use ($recipientUser, $foodPost) {
            $browser->loginAs($recipientUser)
                ->visit('/penerima/katalog')
                ->assertDontSee($foodPost->judul);
        });

        // 4-5. Menggunakan akun Penerima yang sama: kirim request langsung ke endpoint -> ditolak dengan 'Makanan sudah habis.'
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->actingAs($recipientUser);

        $response = $this->post(route('recipient.claims.store', $foodPost), [
            'jumlah_porsi' => 1,
        ]);

        $response->assertSessionHasErrors([
            'jumlah_porsi' => 'Makanan sudah habis.',
        ]);

        // 6. Verifikasi database: tidak ada record klaim yang tersimpan di database
        $this->assertDatabaseMissing('claims', [
            'post_id' => $foodPost->id,
            'recipient_id' => $recipientProfile->id,
        ]);
    }

    // ---------------------------------------------------------------------
    // TC-07-09 : Akun belum terverifikasi
    // ---------------------------------------------------------------------
    public function test_tc_07_09_akun_belum_terverifikasi(): void
    {
        $email = $this->uniqueEmail('pending');
        $namaLembaga = $this->uniqueName('Panti Pending');
        $password = 'Password123!';
        $fixture = base_path('tests/Browser/fixtures/legalitas.pdf');

        $this->browse(function (Browser $browser) use ($email, $namaLembaga, $password, $fixture) {
            $browser->logout();

            // 1. Registrasi Akun Baru via UI (status otomatis menunggu verifikasi)
            $browser->visit('/register')
                ->assertSee('Buat akun');
            $browser->script("document.querySelector('input[name=\"role\"][value=\"penerima\"]').click();");
            $browser->type('@register-nama', $namaLembaga)
                ->type('@register-email', $email)
                ->type('@register-no-hp', '081234567891')
                ->type('@register-password', $password)
                ->type('@register-password-confirmation', $password)
                ->press('@register-next')
                ->pause(500)
                ->assertVisible('@register-nama-lembaga')
                ->type('@register-nama-lembaga', $namaLembaga)
                ->type('@register-jenis-lembaga', 'Panti Asuhan')
                ->type('@register-alamat', 'Jl. Pending No. 3')
                ->select('@register-jenis-dokumen', 'nib')
                ->attach('dokumen', $fixture)
                ->press('@register-submit')
                ->waitForText('Status verifikasi akun')
                ->assertPathIs('/login');

            // 2. Coba login saat akun masih berstatus menunggu verifikasi
            $browser->type('@login-email', $email)
                ->type('@login-password', $password)
                ->press('@login-submit')
                ->waitForText('Akun Anda sedang menunggu verifikasi administrator.')
                ->assertPathIs('/login')
                ->assertSee('menunggu verifikasi');
        });
    }

    // ---------------------------------------------------------------------
    // TC-07-10 : Peran bukan Penerima
    // ---------------------------------------------------------------------
    public function test_tc_07_10_peran_bukan_penerima(): void
    {
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Siap Saji']);
        [$donorUser, $donorProfile] = $this->createVerifiedDonor();
        $foodPost = $this->createFoodPost($donorProfile, $category);

        $adminUser = User::factory()->create([
            'nama' => 'Admin Test',
            'email' => $this->uniqueEmail('admin'),
            'password' => Hash::make('password'),
            'role' => Role::Administrator,
            'status_akun' => AccountStatus::Aktif,
        ]);

        $this->browse(function (Browser $browser) use ($donorUser, $adminUser) {
            // Aktor Donor mencoba akses halaman katalog penerima via browser UI -> 403 Forbidden
            $browser->loginAs($donorUser)
                ->visit('/penerima/katalog')
                ->assertSee('403');

            // Aktor Admin mencoba akses halaman katalog penerima via browser UI -> 403 Forbidden
            $browser->loginAs($adminUser)
                ->visit('/penerima/katalog')
                ->assertSee('403');
        });

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

        // 1. Aktor Donor mencoba akses endpoint klaim penerima -> 403 Forbidden
        $this->actingAs($donorUser);
        $responseDonor = $this->post(route('recipient.claims.store', $foodPost), [
            'jumlah_porsi' => 2,
        ]);
        $responseDonor->assertForbidden();

        // 2. Aktor Admin mencoba akses endpoint klaim penerima -> 403 Forbidden
        $this->actingAs($adminUser);
        $responseAdmin = $this->post(route('recipient.claims.store', $foodPost), [
            'jumlah_porsi' => 2,
        ]);
        $responseAdmin->assertForbidden();

        // Verifikasi database: tidak ada record klaim yang tersimpan
        $this->assertDatabaseMissing('claims', [
            'post_id' => $foodPost->id,
        ]);
    }

    // ---------------------------------------------------------------------
    // TC-07-11 : Belum login
    // ---------------------------------------------------------------------
    public function test_tc_07_11_belum_login(): void
    {
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Siap Saji']);
        [$donorUser, $donorProfile] = $this->createVerifiedDonor();
        $foodPost = $this->createFoodPost($donorProfile, $category);

        $this->browse(function (Browser $browser) {
            $browser->logout()
                ->visit('/penerima/katalog')
                ->waitForLocation('/login')
                ->assertPathIs('/login');
        });

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

        $response = $this->post(route('recipient.claims.store', $foodPost), [
            'jumlah_porsi' => 2,
        ]);

        $response->assertRedirect('/login');

        // Verifikasi database: tidak ada record klaim baru yang dibuat
        $this->assertDatabaseMissing('claims', [
            'post_id' => $foodPost->id,
        ]);
    }

    // ---------------------------------------------------------------------
    // TC-07-12 : Klaim ganda pada post yang sama
    // ---------------------------------------------------------------------
    public function test_tc_07_12_klaim_ganda_pada_post_yang_sama(): void
    {
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Siap Saji']);
        [$donorUser, $donorProfile] = $this->createVerifiedDonor();
        $foodPost = $this->createFoodPost($donorProfile, $category, ['sisa_porsi' => 20, 'jumlah_porsi' => 20]);

        $this->browse(function (Browser $browser) use ($foodPost) {
            [$recipientUser, $recipientProfile] = $this->createVerifiedRecipient();
            $browser->loginAs($recipientUser);

            Claim::create([
                'post_id' => $foodPost->id,
                'recipient_id' => $recipientProfile->id,
                'jumlah_porsi' => 2,
                'status' => 'pending',
                'tanggal_klaim' => now()->toDateString(),
            ]);

            $before = Claim::where('recipient_id', $recipientProfile->id)->count();

            $this->claimViaUi($browser, $foodPost, 3);
            $browser->press('Ajukan Klaim')
                ->waitForText('Anda sudah mengajukan klaim untuk makanan ini.')
                ->assertPathIs('/penerima/katalog');

            $this->assertSame($before, Claim::where('recipient_id', $recipientProfile->id)->count());
        });
    }

    // ---------------------------------------------------------------------
    // TC-07-13 : Waktu respons ≤ 2 detik
    // ---------------------------------------------------------------------
    public function test_tc_07_13_waktu_respons_kurang_dari_atau_sama_dengan_2_detik(): void
    {
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Siap Saji']);
        [$donorUser, $donorProfile] = $this->createVerifiedDonor();
        [$recipientUser, $recipientProfile] = $this->createVerifiedRecipient();
        $foodPost = $this->createFoodPost($donorProfile, $category, ['sisa_porsi' => 20, 'jumlah_porsi' => 20]);

        $this->browse(function (Browser $browser) use ($recipientUser) {
            $browser->loginAs($recipientUser);
            $startTime = microtime(true);
            $browser->visit('/penerima/katalog');
            $duration = microtime(true) - $startTime;

            $browser->assertSee('Katalog Peta Pangan');
            $this->assertLessThanOrEqual(2.0, $duration);
        });

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->actingAs($recipientUser);

        $startTimeApi = microtime(true);
        $response = $this->post(route('recipient.claims.store', $foodPost), [
            'jumlah_porsi' => 2,
        ]);
        $durationApi = microtime(true) - $startTimeApi;

        $response->assertRedirect();
        $this->assertLessThanOrEqual(2.0, $durationApi);
    }
}
