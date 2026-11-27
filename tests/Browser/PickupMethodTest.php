<?php

namespace Tests\Browser;

use App\Enums\AccountStatus;
use App\Enums\FoodPostStatus;
use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Models\Claim;
use App\Models\CourierDetail;
use App\Models\DonorProfile;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\RecipientProfile;
use App\Models\User;
use App\Services\ClaimPickupService;
use DomainException;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PickupMethodTest extends DuskTestCase
{
    private static int $seq = 0;

    private function uniqueEmail(string $prefix): string
    {
        self::$seq++;

        return sprintf('%s-%d-%d-%s@foodbridge.test', strtolower($prefix), time(), self::$seq, strtolower(Str::random(4)));
    }

    private function uniqueName(string $base): string
    {
        self::$seq++;

        return sprintf('%s %d-%s', $base, time(), strtolower(Str::random(4)));
    }

    /**
     * @return array{0: User, 1: DonorProfile}
     */
    private function createDonor(?string $email = null, ?string $namaUsaha = null): array
    {
        $email = $email ?? $this->uniqueEmail('donor');
        $namaUsaha = $namaUsaha ?? $this->uniqueName('Dapur Donor Dusk');

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
            'alamat' => 'Jl. Donor Dusk No. 1, Jakarta',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        return [$user, $profile];
    }

    /**
     * @return array{0: User, 1: RecipientProfile}
     */
    private function createRecipient(?string $email = null, ?string $namaLembaga = null): array
    {
        $email = $email ?? $this->uniqueEmail('penerima');
        $namaLembaga = $namaLembaga ?? $this->uniqueName('Panti Penerima Dusk');

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
            'alamat' => 'Jl. Penerima Dusk No. 2, Jakarta',
            'lat' => -6.21,
            'lng' => 106.81,
        ]);

        return [$user, $profile];
    }

    private function createFoodPost(DonorProfile $donorProfile, FoodCategory $category, array $overrides = []): FoodPost
    {
        return FoodPost::create(array_merge([
            'judul' => 'Donasi Makanan '.Str::random(6),
            'deskripsi' => 'Makanan untuk pengujian metode pengambilan Dusk.',
            'foto' => 'seed/placeholder.webp',
            'jumlah_porsi' => 10,
            'sisa_porsi' => 10,
            'alamat' => $donorProfile->alamat,
            'lat' => $donorProfile->lat,
            'lng' => $donorProfile->lng,
            'batas_konsumsi' => now()->addHours(12),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donorProfile->id,
            'category_id' => $category->id,
        ], $overrides));
    }

    private function createClaim(FoodPost $post, RecipientProfile $recipientProfile, array $overrides = []): Claim
    {
        return Claim::create(array_merge([
            'post_id' => $post->id,
            'recipient_id' => $recipientProfile->id,
            'jumlah_porsi' => 2,
            'status' => 'approved',
            'metode_pengambilan' => null,
            'kode_verifikasi' => null,
            'tanggal_klaim' => now()->toDateString(),
            'approved_at' => now(),
            'completed_at' => null,
        ], $overrides));
    }

    private function shouldRunUiRegistration(): bool
    {
        $argv = $_SERVER['argv'] ?? [];

        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--filter=') || $arg === '--filter') {
                return false;
            }
        }

        return true;
    }

    private function registerAndLoginRecipient(Browser $browser, ?string $email = null, ?string $namaLembaga = null): array
    {
        $email = $email ?? $this->uniqueEmail('penerima');
        $namaLembaga = $namaLembaga ?? $this->uniqueName('Panti Penerima Dusk');
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
            ->type('@register-alamat', 'Jl. Penerima Dusk No. 2, Jakarta')
            ->select('@register-jenis-dokumen', 'nib')
            ->attach('dokumen', $fixture)
            ->press('@register-submit')
            ->waitForText('Status verifikasi akun')
            ->assertPathIs('/login');

        // 2. Verifikasi dan aktifkan akun di DB agar dapat masuk ke dashboard
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

    // ---------------------------------------------------------------------
    // TC-11-01: Pemilihan Metode Ambil Sendiri (UI Browser)
    // ---------------------------------------------------------------------
    public function test_tc_11_01_penerima_pilih_ambil_sendiri(): void
    {
        [$donorUser, $donorProfile] = $this->createDonor();
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Dusk']);
        $post = $this->createFoodPost($donorProfile, $category, ['judul' => 'Nasi Box Mandiri '.Str::random(4)]);

        $this->browse(function (Browser $browser) use ($donorUser, $post) {
            if ($this->shouldRunUiRegistration()) {
                // Registrasi dan login Penerima via UI jika dijalankan 1 file penuh
                [$recipientUser, $recipientProfile] = $this->registerAndLoginRecipient($browser);
            } else {
                // Langsung buat akun dan login jika dijalankan 1 tc saja
                [$recipientUser, $recipientProfile] = $this->createRecipient();
                $browser->loginAs($recipientUser);
            }

            $claim = $this->createClaim($post, $recipientProfile);

            // 1. Buka menu Klaim Saya, klik tombol hijau "Pilih Metode" pada klaim disetujui
            $browser->visit('/recipient/claims')
                ->waitForText('Klaim Saya')
                ->waitFor('@claim-detail-'.$claim->id)
                ->assertSee('Pilih Metode')
                ->click('@claim-detail-'.$claim->id)
                // 2. Pada modal rincian klaim, pilih opsi radio "Ambil Sendiri", lalu tekan "Simpan Metode Pengambilan"
                ->waitFor('@pickup-method-section-'.$claim->id)
                ->assertSee('Pilih Metode Pengambilan')
                ->click('@method-mandiri-'.$claim->id)
                ->press('@submit-pickup-method-'.$claim->id)
                // 3. Sistem memproses penyimpanan metode dan mengalihkan kembali ke daftar klaim disertai pesan sukses
                ->waitForText('Metode pengambilan berhasil disimpan.')
                // 4. Modal otomatis terbuka menampilkan rincian "Metode: Ambil Sendiri", alamat donor, dan kontak donor
                ->waitFor('@claim-detail-modal-'.$claim->id)
                ->assertSee('Metode: Ambil Sendiri')
                ->assertSee($post->alamat)
                ->assertDontSee('Simpan Metode Pengambilan');

            // 5. Verifikasi database: claims.metode_pengambilan = 'ambil_sendiri' dan notifikasi in-app tercatat untuk akun Donor
            $this->assertDatabaseHas('claims', [
                'id' => $claim->id,
                'metode_pengambilan' => 'ambil_sendiri',
            ]);

            $this->assertDatabaseMissing('courier_details', [
                'claim_id' => $claim->id,
            ]);

            $this->assertDatabaseHas('notifications', [
                'user_id' => $donorUser->id,
            ]);
        });
    }

    // ---------------------------------------------------------------------
    // TC-11-02: Pemilihan Metode Kurir Online (UI Browser)
    // ---------------------------------------------------------------------
    public function test_tc_11_02_penerima_pilih_kurir_online(): void
    {
        [$donorUser, $donorProfile] = $this->createDonor();
        [$recipientUser, $recipientProfile] = $this->createRecipient();
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Dusk']);
        $post = $this->createFoodPost($donorProfile, $category, ['judul' => 'Ayam Bakar Kurir '.Str::random(4)]);
        $claim = $this->createClaim($post, $recipientProfile);

        $fixturePhoto = base_path('tests/Browser/fixtures/courier_proof.png');
        $namaDriver = 'Budi Santoso Dusk '.Str::random(3);
        $platDriver = 'D 1234 DSK';
        $kodeDriver = 'GJ-998811';

        $this->browse(function (Browser $browser) use ($recipientUser, $claim, $fixturePhoto, $namaDriver, $platDriver, $kodeDriver) {
            // 1. Login Penerima 1, buka klaim disetujui di /recipient/claims, klik "Pilih Metode", lalu pilih opsi radio "Kurir Online"
            $browser->loginAs($recipientUser)
                ->visit('/recipient/claims')
                ->waitFor('@claim-detail-'.$claim->id)
                ->click('@claim-detail-'.$claim->id)
                ->waitFor('@pickup-method-section-'.$claim->id)
                ->click('@method-kurir-'.$claim->id)
                // 2. Lengkapi data pengemudi: platform, nama, no plat, kode kurir, dan unggah foto bukti pesanan
                ->waitFor('@courier-nama-'.$claim->id)
                ->select('@courier-platform-'.$claim->id, 'Gojek')
                ->type('@courier-nama-'.$claim->id, $namaDriver)
                ->type('@courier-plat-'.$claim->id, $platDriver)
                ->type('@courier-kode-'.$claim->id, $kodeDriver)
                ->attach('@courier-foto-'.$claim->id, $fixturePhoto)
                // 3. Tekan tombol "Simpan Metode Pengambilan"
                ->press('@submit-pickup-method-'.$claim->id)
                // 4. Sistem menyimpan berkas foto ke storage publik dan mengalihkan kembali ke daftar klaim dengan pesan sukses
                ->waitForText('Metode pengambilan berhasil disimpan.')
                // 5. Modal otomatis menampilkan kartu Detail Kurir lengkap dengan pratinjau foto bukti (formulir input terkunci)
                ->waitFor('@recipient-courier-details-'.$claim->id)
                ->assertSee('Platform')
                ->assertSee('Gojek')
                ->assertSee($namaDriver)
                ->assertSee($platDriver)
                ->assertSee($kodeDriver)
                ->assertDontSee('Simpan Metode Pengambilan');
        });

        // 6. Verifikasi database: claims.metode_pengambilan = 'kurir_online', record courier_details terbentuk, dan notifikasi in-app terkirim ke Donor
        $this->assertDatabaseHas('claims', [
            'id' => $claim->id,
            'metode_pengambilan' => 'kurir_online',
        ]);

        $this->assertDatabaseHas('courier_details', [
            'claim_id' => $claim->id,
            'platform' => 'Gojek',
            'nama_pengemudi' => $namaDriver,
            'no_kendaraan' => $platDriver,
            'kode_verifikasi_kurir' => $kodeDriver,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $donorUser->id,
        ]);
    }

    // ---------------------------------------------------------------------
    // TC-11-03: Tampilan Rincian Kurir pada Sisi Donor (UI Browser)
    // ---------------------------------------------------------------------
    public function test_tc_11_03_donor_dapat_melihat_rincian_kurir(): void
    {
        [$donorUser, $donorProfile] = $this->createDonor();
        [$recipientUser, $recipientProfile] = $this->createRecipient();
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Dusk']);
        $post = $this->createFoodPost($donorProfile, $category, ['judul' => 'Sop Daging Kurir '.Str::random(4)]);

        // 1. Siapkan klaim disetujui dengan metode kurir_online dan data pengemudi tersimpan di database
        $claim = $this->createClaim($post, $recipientProfile, ['metode_pengambilan' => 'kurir_online']);
        $namaDriver = 'Ahmad Kurir Dusk '.Str::random(3);

        CourierDetail::create([
            'claim_id' => $claim->id,
            'platform' => 'Grab',
            'nama_pengemudi' => $namaDriver,
            'no_kendaraan' => 'B 9900 GRB',
            'kode_verifikasi_kurir' => 'GRAB-77',
            'foto_bukti' => 'seed/placeholder.webp',
            'created_at' => now(),
        ]);

        $this->browse(function (Browser $browser) use ($donorUser, $claim, $namaDriver) {
            // 2. Login sebagai Donor pemilik makanan, lalu akses halaman Klaim Masuk (/donor/claims)
            $browser->loginAs($donorUser)
                ->visit('/donor/claims')
                ->assertSee('Klaim Masuk')
                // 3. Verifikasi baris klaim pada tabel menampilkan keterangan status: "Kurir Online: [Nama Pengemudi]"
                ->waitForText('Kurir Online: '.$namaDriver)
                ->assertSee('Kurir Online: '.$namaDriver)
                // 4. Klik tombol "Detail" pada baris klaim tersebut untuk membuka modal rincian
                ->click('@donor-claim-detail-'.$claim->id)
                // 5. Modal menampilkan kartu Data Pengemudi Kurir (platform, nama, plat kendaraan, kode verifikasi)
                ->waitFor('@donor-courier-details-'.$claim->id)
                ->assertSee('Grab')
                ->assertSee($namaDriver)
                ->assertSee('B 9900 GRB')
                ->assertSee('GRAB-77');
        });
    }

    // ---------------------------------------------------------------------
    // TC-11-04: Validasi Form Kurir Saat Field Kosong (UI Browser)
    // ---------------------------------------------------------------------
    public function test_tc_11_04_validasi_form_kurir_saat_field_kosong(): void
    {
        [$donorUser, $donorProfile] = $this->createDonor();
        [$recipientUser, $recipientProfile] = $this->createRecipient();
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Dusk']);
        $post = $this->createFoodPost($donorProfile, $category, ['judul' => 'Kue Bolu Dusk '.Str::random(4)]);
        $claim = $this->createClaim($post, $recipientProfile);

        $this->browse(function (Browser $browser) use ($recipientUser, $claim) {
            // 1. Login Penerima 1, buka klaim disetujui, klik "Pilih Metode", dan pilih opsi "Kurir Online"
            $browser->loginAs($recipientUser)
                ->visit('/recipient/claims')
                ->waitFor('@claim-detail-'.$claim->id)
                ->click('@claim-detail-'.$claim->id)
                ->waitFor('@pickup-method-section-'.$claim->id)
                ->click('@method-kurir-'.$claim->id)
                ->waitFor('@courier-nama-'.$claim->id)
                // 2. Kosongkan field wajib nama pengemudi, lalu tekan "Simpan Metode Pengambilan"
                ->press('@submit-pickup-method-'.$claim->id)
                // 3-4. Sistem membatalkan penyimpanan; modal auto-reopen menampilkan alert error
                ->waitFor('@pickup-method-errors-'.$claim->id)
                ->assertSee('Gagal menyimpan data pengemudi')
                ->assertSee('nama pengemudi wajib diisi.');
        });

        // 5. Verifikasi database: metode tetap kosong (null) dan data kurir tidak terbentuk
        $this->assertDatabaseHas('claims', [
            'id' => $claim->id,
            'metode_pengambilan' => null,
        ]);

        $this->assertDatabaseMissing('courier_details', [
            'claim_id' => $claim->id,
        ]);
    }

    // ---------------------------------------------------------------------
    // TC-11-05: Validasi Format Foto Bukti Bukan Gambar (UI Browser)
    // ---------------------------------------------------------------------
    public function test_tc_11_05_validasi_format_foto_bukti_bukan_gambar(): void
    {
        [$donorUser, $donorProfile] = $this->createDonor();
        [$recipientUser, $recipientProfile] = $this->createRecipient();
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Dusk']);
        $post = $this->createFoodPost($donorProfile, $category, ['judul' => 'Bolu Format Dusk '.Str::random(4)]);
        $claim = $this->createClaim($post, $recipientProfile);

        $fixturePdf = base_path('tests/Browser/fixtures/legalitas.pdf');

        $this->browse(function (Browser $browser) use ($recipientUser, $claim, $fixturePdf) {
            // 1. Buka modal rincian klaim disetujui pada akun Penerima 1, pilih "Kurir Online", dan lengkapi data teks pengemudi
            $browser->loginAs($recipientUser)
                ->visit('/recipient/claims')
                ->waitFor('@claim-detail-'.$claim->id)
                ->click('@claim-detail-'.$claim->id)
                ->waitFor('@pickup-method-section-'.$claim->id)
                ->click('@method-kurir-'.$claim->id)
                ->waitFor('@courier-nama-'.$claim->id)
                ->select('@courier-platform-'.$claim->id, 'Gojek')
                ->type('@courier-nama-'.$claim->id, 'Budi Driver')
                ->type('@courier-plat-'.$claim->id, 'B 1111 TST')
                ->type('@courier-kode-'.$claim->id, 'GJ-123')
                // 2. Pada input tangkapan layar, unggah berkas selain format gambar (PDF)
                ->attach('@courier-foto-'.$claim->id, $fixturePdf)
                // 3. Tekan "Simpan Metode Pengambilan"
                ->press('@submit-pickup-method-'.$claim->id)
                // 4. Sistem menolak request dan modal tetap terbuka menampilkan error
                ->waitFor('@pickup-method-errors-'.$claim->id)
                ->assertSee('Foto harus berformat JPG, JPEG, PNG, atau WEBP.');
        });

        // 5. Verifikasi storage & database: data kurir tidak tersimpan di database
        $this->assertDatabaseHas('claims', [
            'id' => $claim->id,
            'metode_pengambilan' => null,
        ]);

        $this->assertDatabaseMissing('courier_details', [
            'claim_id' => $claim->id,
        ]);
    }

    // ---------------------------------------------------------------------
    // TC-11-06: Validasi Ukuran Foto Bukti Melebihi 2 MB (UI Browser)
    // ---------------------------------------------------------------------
    public function test_tc_11_06_validasi_ukuran_foto_bukti_melebihi_2_mb(): void
    {
        [$donorUser, $donorProfile] = $this->createDonor();
        [$recipientUser, $recipientProfile] = $this->createRecipient();
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Dusk']);
        $post = $this->createFoodPost($donorProfile, $category, ['judul' => 'Bolu Ukuran Dusk '.Str::random(4)]);
        $claim = $this->createClaim($post, $recipientProfile);

        // Oversize fixture dibuat saat runtime: PNG valid kecil + padding
        // hingga > 2 MB. Header PNG tetap utuh sehingga lolos validasi
        // image/mimes dan gagal tepat di aturan max (2 MB).
        $fixtureLarge = sys_get_temp_dir().'/dusk-large-'.Str::random(8).'.png';
        file_put_contents(
            $fixtureLarge,
            file_get_contents(base_path('tests/Browser/fixtures/courier_proof.png')).random_bytes(2_600_000)
        );

        try {
            $this->browse(function (Browser $browser) use ($recipientUser, $claim, $fixtureLarge) {
            // 1. Buka modal klaim disetujui pada akun Penerima 1, pilih "Kurir Online", dan isi seluruh data pengemudi
            $browser->loginAs($recipientUser)
                ->visit('/recipient/claims')
                ->waitFor('@claim-detail-'.$claim->id)
                ->click('@claim-detail-'.$claim->id)
                ->waitFor('@pickup-method-section-'.$claim->id)
                ->click('@method-kurir-'.$claim->id)
                ->waitFor('@courier-nama-'.$claim->id)
                ->select('@courier-platform-'.$claim->id, 'Gojek')
                ->type('@courier-nama-'.$claim->id, 'Budi Driver')
                ->type('@courier-plat-'.$claim->id, 'B 1111 TST')
                ->type('@courier-kode-'.$claim->id, 'GJ-123')
                // 2. Unggah berkas gambar dengan ukuran melebihi batas maksimal 2 MB
                ->attach('@courier-foto-'.$claim->id, $fixtureLarge)
                // 3. Tekan "Simpan Metode Pengambilan"
                ->press('@submit-pickup-method-'.$claim->id)
                // 4. Sistem menolak berkas; modal tetap terbuka menampilkan pesan kesalahan validasi
                ->waitFor('@pickup-method-errors-'.$claim->id)
                ->assertSee('Ukuran foto maksimal 2 MB.');
            });
        } finally {
            @unlink($fixtureLarge);
        }

        // 5. Verifikasi database: data metode pengambilan tetap kosong
        $this->assertDatabaseHas('claims', [
            'id' => $claim->id,
            'metode_pengambilan' => null,
        ]);

        $this->assertDatabaseMissing('courier_details', [
            'claim_id' => $claim->id,
        ]);
    }

    // ---------------------------------------------------------------------
    // TC-11-07: Pencegahan Pemilihan Metode pada Klaim Pending dan Ditolak (UI Browser)
    // ---------------------------------------------------------------------
    public function test_tc_11_07_pencegahan_pemilihan_metode_pada_klaim_pending_dan_ditolak(): void
    {
        [$donorUser, $donorProfile] = $this->createDonor();
        [$recipientUser, $recipientProfile] = $this->createRecipient();
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Dusk']);
        $post = $this->createFoodPost($donorProfile, $category, ['judul' => 'Soto Ayam Pending '.Str::random(4)]);
        $claimPending = $this->createClaim($post, $recipientProfile, ['status' => 'pending', 'approved_at' => null]);
        $claimRejected = $this->createClaim($post, $recipientProfile, ['status' => 'rejected', 'approved_at' => null]);

        $this->browse(function (Browser $browser) use ($recipientUser, $claimPending, $claimRejected) {
            // 1. Login Penerima 1, buka halaman Klaim Saya, periksa klaim berstatus Menunggu dan Ditolak
            $browser->loginAs($recipientUser)
                ->visit('/recipient/claims')
                // 2. Verifikasi UI tabel: tombol yang tersedia hanya tombol "Detail", tombol hijau "Pilih Metode" tidak ditampilkan
                ->waitFor('@claim-detail-'.$claimPending->id)
                ->assertSeeIn('@claim-detail-'.$claimPending->id, 'Detail')
                // 3. Buka modal detail pada kedua klaim tersebut dan verifikasi formulir pemilihan metode tidak tersedia
                ->click('@claim-detail-'.$claimPending->id)
                ->waitFor('@claim-detail-modal-'.$claimPending->id)
                ->assertDontSee('Pilih Metode Pengambilan')
                ->visit('/recipient/claims')
                ->waitFor('@claim-detail-'.$claimRejected->id)
                ->assertSeeIn('@claim-detail-'.$claimRejected->id, 'Detail')
                ->click('@claim-detail-'.$claimRejected->id)
                ->waitFor('@claim-detail-modal-'.$claimRejected->id)
                ->assertDontSee('Pilih Metode Pengambilan');
        });

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->actingAs($recipientUser);

        // 4-5. Coba kirim request POST langsung ke endpoint untuk klaim pending -> ditolak 'Klaim belum disetujui.'
        $responsePending = $this->post(route('recipient.claims.pickup-method.store', $claimPending), [
            'metode_pengambilan' => 'ambil_sendiri',
        ]);
        $responsePending->assertRedirect();
        $responsePending->assertSessionHas('error', 'Klaim belum disetujui.');
        $this->assertNull(Claim::find($claimPending->id)->metode_pengambilan);

        // Coba kirim request POST langsung ke endpoint untuk klaim rejected -> ditolak 'Klaim belum disetujui.'
        $responseRejected = $this->post(route('recipient.claims.pickup-method.store', $claimRejected), [
            'metode_pengambilan' => 'ambil_sendiri',
        ]);
        $responseRejected->assertRedirect();
        $responseRejected->assertSessionHas('error', 'Klaim belum disetujui.');
        $this->assertNull(Claim::find($claimRejected->id)->metode_pengambilan);
    }

    // ---------------------------------------------------------------------
    // TC-11-08: Proteksi Imutabilitas Metode yang Sudah Disimpan (UI Browser)
    // ---------------------------------------------------------------------
    public function test_tc_11_08_proteksi_imutabilitas_metode_yang_sudah_disimpan(): void
    {
        [$donorUser, $donorProfile] = $this->createDonor();
        [$recipientUser, $recipientProfile] = $this->createRecipient();
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Dusk']);
        $post = $this->createFoodPost($donorProfile, $category, ['judul' => 'Roti Manis Final '.Str::random(4)]);

        // 1. Siapkan klaim disetujui yang sudah berhasil disimpan metodenya sebagai ambil_sendiri
        $claim = $this->createClaim($post, $recipientProfile, ['metode_pengambilan' => 'ambil_sendiri']);

        $this->browse(function (Browser $browser) use ($recipientUser, $claim) {
            // 2. Buka detail klaim tersebut di browser; modal terkunci dan hanya menampilkan rincian metode yang sudah dipilih
            $browser->loginAs($recipientUser)
                ->visit('/recipient/claims')
                ->waitFor('@claim-detail-'.$claim->id)
                ->click('@claim-detail-'.$claim->id)
                ->waitFor('@claim-detail-modal-'.$claim->id)
                ->assertSee('Metode: Ambil Sendiri')
                ->assertDontSee('Pilih Metode Pengambilan');
        });

        // 3. Kirim request HTTP POST ulang ke endpoint klaim yang sama dengan payload metode baru (kurir_online)
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->actingAs($recipientUser);

        $photo = UploadedFile::fake()->create('proof.jpg', 100, 'image/jpeg');
        $response = $this->post(route('recipient.claims.pickup-method.store', $claim), [
            'metode_pengambilan' => 'kurir_online',
            'platform' => 'Gojek',
            'nama_pengemudi' => 'Driver Baru',
            'no_kendaraan' => 'B 2222 NEW',
            'kode_verifikasi_kurir' => 'GJ-999',
            'foto_bukti' => $photo,
        ]);

        // 4. Sistem menolak request dan mengembalikan pesan kesalahan domain: 'Metode pengambilan sudah dipilih.'
        $response->assertRedirect();
        $response->assertSessionHas('error', 'Metode pengambilan sudah dipilih.');

        // 5. Verifikasi database: nilai kolom claims.metode_pengambilan tetap 'ambil_sendiri' (data awal tidak berubah)
        $claim->refresh();
        $this->assertSame('ambil_sendiri', $claim->metode_pengambilan);
    }

    // ---------------------------------------------------------------------
    // TC-11-09: Validasi Input Nilai Metode di Luar Whitelist
    // ---------------------------------------------------------------------
    public function test_tc_11_09_validasi_input_nilai_metode_di_luar_whitelist(): void
    {
        [$donorUser, $donorProfile] = $this->createDonor();
        [$recipientUser, $recipientProfile] = $this->createRecipient();
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Dusk']);
        $post = $this->createFoodPost($donorProfile, $category);
        $claim = $this->createClaim($post, $recipientProfile);

        // 1-2. Login sebagai Penerima, kirim request HTTP POST dengan metode_pengambilan = "ojek"
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->actingAs($recipientUser);

        $response = $this->post(route('recipient.claims.pickup-method.store', $claim), [
            'metode_pengambilan' => 'ojek',
        ]);

        // 3. Validasi FormRequest menolak request dan mengembalikan session error pada field metode_pengambilan
        $response->assertSessionHasErrors('metode_pengambilan');

        // 4. Verifikasi database: kolom claims.metode_pengambilan tetap bernilai null
        $claim->refresh();
        $this->assertNull($claim->metode_pengambilan);
        $this->assertDatabaseMissing('courier_details', [
            'claim_id' => $claim->id,
        ]);
    }

    // ---------------------------------------------------------------------
    // TC-11-10: Proteksi Makanan yang Melewati Batas Konsumsi (Kedaluwarsa)
    // ---------------------------------------------------------------------
    public function test_tc_11_10_proteksi_makanan_yang_melewati_batas_konsumsi_kedaluwarsa(): void
    {
        [$donorUser, $donorProfile] = $this->createDonor();
        [$recipientUser, $recipientProfile] = $this->createRecipient();
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Dusk']);

        // 1. Siapkan klaim disetujui pada postingan makanan yang telah melewati batas konsumsi (2 jam lalu)
        $post = $this->createFoodPost($donorProfile, $category, [
            'batas_konsumsi' => now()->subHours(2),
        ]);
        $claim = $this->createClaim($post, $recipientProfile);

        // 2. Login sebagai Penerima 1, lalu kirim request penyimpanan metode pengambilan
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->actingAs($recipientUser);

        $response = $this->post(route('recipient.claims.pickup-method.store', $claim), [
            'metode_pengambilan' => 'ambil_sendiri',
        ]);

        // 3-4. Sistem mendeteksi status kedaluwarsa dan mengembalikan pesan error: 'Makanan sudah melewati batas konsumsi.'
        $response->assertRedirect();
        $response->assertSessionHas('error', 'Makanan sudah melewati batas konsumsi.');

        // 5. Verifikasi database: kolom claims.metode_pengambilan tetap bernilai null
        $claim->refresh();
        $this->assertNull($claim->metode_pengambilan);
    }

    // ---------------------------------------------------------------------
    // TC-11-11: Keandalan Transaksi Database Atomik dan Rollback File saat Error
    // ---------------------------------------------------------------------
    public function test_tc_11_11_keandalan_transaksi_database_atomik_dan_rollback_file_saat_error(): void
    {
        [$donorUser, $donorProfile] = $this->createDonor();
        [$recipientUser, $recipientProfile] = $this->createRecipient();
        $category = FoodCategory::firstOrCreate(['nama_kategori' => 'Makanan Dusk']);
        $post = $this->createFoodPost($donorProfile, $category);
        $claim = $this->createClaim($post, $recipientProfile);

        // 1. Siapkan klaim disetujui dan simulasikan kegagalan sistem saat proses insert courier_details
        Storage::fake('public');
        $photo = UploadedFile::fake()->create('rollback_proof.jpg', 100, 'image/jpeg');

        CourierDetail::saving(function () {
            throw new Exception('Simulasi kegagalan database pada CourierDetail.');
        });

        $service = app(ClaimPickupService::class);

        // 2-3. Kirim request pemilihan metode Kurir Online -> sistem menangkap exception & rollback
        try {
            $service->choosePickupMethod(
                $claim,
                [
                    'metode_pengambilan' => 'kurir_online',
                    'platform' => 'Gojek',
                    'nama_pengemudi' => 'Driver Rollback',
                    'no_kendaraan' => 'B 9999 ERR',
                    'kode_verifikasi_kurir' => 'ERR-01',
                ],
                $photo,
                $recipientUser
            );
            $this->fail('Harusnya melempar exception saat simulasi rollback.');
        } catch (Exception $e) {
            $this->assertStringContainsString('Simulasi kegagalan database', $e->getMessage());
        }

        CourierDetail::flushEventListeners();

        // 4-5. Verifikasi: claims.metode_pengambilan tetap kosong (null), courier_details bersih, dan storage bersih
        $this->assertDatabaseHas('claims', [
            'id' => $claim->id,
            'metode_pengambilan' => null,
        ]);

        $this->assertDatabaseMissing('courier_details', [
            'claim_id' => $claim->id,
        ]);

        $files = Storage::disk('public')->allFiles('courier_proofs');
        $this->assertEmpty($files, 'Storage harus bersih dari file yatim setelah rollback.');
    }
}
