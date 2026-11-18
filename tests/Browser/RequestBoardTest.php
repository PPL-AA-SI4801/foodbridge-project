<?php

namespace Tests\Browser;

use App\Enums\FoodRequestStatus;
use App\Enums\Role;
use App\Enums\UrgencyLevel;
use App\Models\FoodCategory;
use App\Models\FoodRequest;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class RequestBoardTest extends DuskTestCase
{
    private static int $seq = 0;

    private function uniqueEmail(string $prefix): string
    {
        self::$seq++;

        return sprintf('%s-%d-%d-%s@example.com', strtolower($prefix), time(), self::$seq, strtolower(Str::random(4)));
    }

    private function uniqueName(string $base): string
    {
        self::$seq++;

        return sprintf('%s %d-%d-%s', $base, time(), self::$seq, strtolower(Str::random(4)));
    }

    /**
     * Penerima mendaftar dan login melalui GUI, akun diaktifkan via DB, lalu membuat 2 permintaan kebutuhan makanan.
     */
    public function test_penerima_can_create_food_request_from_request_board(): void
    {
        Artisan::call('migrate:fresh');

        $category = FoodCategory::create(['nama_kategori' => 'Nasi & Lauk']);
        $fixture = base_path('tests/Browser/fixtures/legalitas.pdf');
        $email = $this->uniqueEmail('penerima');
        $namaLembaga = $this->uniqueName('Panti Asuhan Harapan');

        $this->browse(function (Browser $browser) use ($category, $fixture, $email, $namaLembaga) {
            $browser->logout()
                ->visit('/register')
                ->assertSee('Buat akun');

            $browser->script("document.querySelector('input[name=\"role\"][value=\"penerima\"]').click();");

            $browser->type('@register-nama', 'Pengurus Panti Harapan')
                ->type('@register-email', $email)
                ->type('@register-no-hp', '081234567891')
                ->type('@register-password', 'Password123!')
                ->type('@register-password-confirmation', 'Password123!')
                ->press('@register-next')
                ->pause(500)
                ->assertVisible('@register-nama-lembaga')
                ->type('@register-nama-lembaga', $namaLembaga)
                ->type('@register-jenis-lembaga', 'Panti Asuhan')
                ->type('@register-alamat', 'Jl. Penerima No. 2, Jakarta')
                ->select('@register-jenis-dokumen', 'nib')
                ->attach('dokumen', $fixture)
                ->press('@register-submit')
                ->waitForText('Pendaftaran berhasil. Lengkapi dokumen Anda agar proses verifikasi bisa dilanjutkan.')
                ->assertPathIs('/login');

            User::where('email', $email)->update(['status_akun' => 'aktif']);

            $browser->type('@login-email', $email)
                ->type('@login-password', 'Password123!')
                ->press('@login-submit')
                ->waitForLocation('/dashboard/penerima')
                ->visit('/recipient/requests')
                ->assertSee('Siarkan Kebutuhan Pangan')
                ->press('Buat Permintaan Kebutuhan')
                ->waitFor('#judul')
                ->type('judul', 'Nasi Kotak untuk Anak Panti')
                ->select('category_id', (string) $category->id)
                ->clear('jumlah_porsi')
                ->type('jumlah_porsi', '30')
                ->select('urgency_level', UrgencyLevel::Darurat->value)
                ->select('metode_penerimaan', 'diantar_kurir')
                ->type('deskripsi', 'Makanan halal tanpa pedas.')
                ->press('Siarkan ke Request Board')
                ->waitForText('Permintaan kebutuhan makanan berhasil disiarkan ke Request Board')
                ->assertPathIs('/recipient/requests')
                ->assertSee('Nasi Kotak untuk Anak Panti')
                ->assertSee('30 porsi dibutuhkan');

            $browser->press('Buat Permintaan Kebutuhan')
                ->waitFor('#judul')
                ->type('judul', 'Permintaan Bantuan Pangan Mendesak')
                ->select('category_id', (string) $category->id)
                ->clear('jumlah_porsi')
                ->type('jumlah_porsi', '20')
                ->select('urgency_level', UrgencyLevel::Mendesak->value)
                ->select('metode_penerimaan', 'fleksibel')
                ->type('deskripsi', 'Kebutuhan pangan tambahan untuk panti.')
                ->press('Siarkan ke Request Board')
                ->waitForText('Permintaan kebutuhan makanan berhasil disiarkan ke Request Board')
                ->assertPathIs('/recipient/requests')
                ->assertSee('Permintaan Bantuan Pangan Mendesak')
                ->assertSee('20 porsi dibutuhkan')
                ->logout();
        });
    }

    /**
     * Donor mendaftar dan login melalui GUI, akun diaktifkan via DB, lalu memenuhi permintaan pertama dari test sebelumnya.
     */
    public function test_donor_can_fulfil_open_request_on_request_board(): void
    {
        $fixture = base_path('tests/Browser/fixtures/legalitas.pdf');
        $email = $this->uniqueEmail('donor');
        $namaUsaha = $this->uniqueName('Resto Berkah');
        $foodRequest = FoodRequest::where('judul', 'Nasi Kotak untuk Anak Panti')->firstOrFail();

        $this->browse(function (Browser $browser) use ($fixture, $email, $namaUsaha, $foodRequest) {
            $browser->logout()
                ->visit('/register')
                ->assertSee('Buat akun')
                ->type('@register-nama', 'Pemilik Resto Berkah')
                ->type('@register-email', $email)
                ->type('@register-no-hp', '081234567890')
                ->type('@register-password', 'Password123!')
                ->type('@register-password-confirmation', 'Password123!')
                ->press('@register-next')
                ->pause(500)
                ->assertVisible('@register-nama-usaha')
                ->type('@register-nama-usaha', $namaUsaha)
                ->type('@register-jenis-usaha', 'Restoran')
                ->type('@register-alamat', 'Jl. Donor No. 1, Jakarta')
                ->select('@register-jenis-dokumen', 'nib')
                ->attach('dokumen', $fixture)
                ->press('@register-submit')
                ->waitForText('Pendaftaran berhasil. Lengkapi dokumen Anda agar proses verifikasi bisa dilanjutkan.')
                ->assertPathIs('/login');

            User::where('email', $email)->update(['status_akun' => 'aktif']);

            $browser->type('@login-email', $email)
                ->type('@login-password', 'Password123!')
                ->press('@login-submit')
                ->waitForLocation('/dashboard/donor')
                ->visit('/donor/requests')
                ->assertSee('Papan Kebutuhan Pangan Penerima')
                ->assertSee('Nasi Kotak untuk Anak Panti')
                ->press('Penuhi Kebutuhan Ini')
                ->waitFor("#post_id_{$foodRequest->id}")
                ->press('Konfirmasi Pemenuhan')
                ->waitForText('Terima kasih! Anda berhasil merespons kebutuhan makanan ini')
                ->assertPathIs('/donor/requests')
                ->assertSee('Sedang Diproses')
                ->logout();
        });
    }

    /**
     * Administrator diinjeksi ke DB, login melalui GUI, dan menutup permintaan kedua yang dibuat pada test pertama.
     */
    public function test_administrator_can_view_and_moderate_request_board(): void
    {
        $adminEmail = $this->uniqueEmail('admin');

        User::factory()->create([
            'email' => $adminEmail,
            'password' => Hash::make('Password123!'),
            'role' => Role::Administrator,
            'status_akun' => 'aktif',
        ]);

        $this->browse(function (Browser $browser) use ($adminEmail) {
            $browser->logout()
                ->visit('/login')
                ->type('@login-email', $adminEmail)
                ->type('@login-password', 'Password123!')
                ->press('@login-submit')
                ->waitForLocation('/dashboard/admin')
                ->visit('/admin/requests')
                ->assertSee('Request Board')
                ->assertSee('Permintaan Bantuan Pangan Mendesak')
                ->press('Tutup')
                ->acceptDialog()
                ->waitForText('Permintaan kebutuhan makanan berhasil ditutup oleh Administrator')
                ->assertPathIs('/admin/requests')
                ->assertSee('Dibatalkan')
                ->logout();
        });

        $this->assertDatabaseHas('food_requests', [
            'judul' => 'Permintaan Bantuan Pangan Mendesak',
            'status' => FoodRequestStatus::Cancelled->value,
        ]);
    }
}

