<?php

namespace Tests\Browser;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AuthenticationTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * TC-2-01: Donor can register with a legal document.
     *
     * Real app redirects to /login with a waiting-verification notice
     * (not an "awaiting verification" info page).
     */
    public function test_donor_can_register_with_legal_document(): void
    {
        $fixture = base_path('tests/Browser/fixtures/legalitas.pdf');

        $this->browse(function (Browser $browser) use ($fixture) {
            $browser->visit('/register')
                ->assertSee('Buat akun')
                ->type('@register-nama', 'Donor Test Satu')
                ->type('@register-email', 'donor@example.com')
                ->type('@register-no-hp', '081234567890')
                ->type('@register-password', 'Password123!')
                ->type('@register-password-confirmation', 'Password123!')
                ->press('@register-next')
                ->pause(500)
                ->assertVisible('@register-nama-usaha')
                ->type('@register-nama-usaha', 'Resto Donor Satu')
                ->type('@register-jenis-usaha', 'Restoran')
                ->type('@register-alamat', 'Jl. Merdeka No. 1, Bandung')
                ->select('@register-jenis-dokumen', 'nib')
                ->attach('dokumen', $fixture)
                ->press('@register-submit')
                ->waitForText('Pendaftaran berhasil. Akun Anda sedang menunggu verifikasi administrator.')
                ->assertPathIs('/login')
                ->assertSee('Pendaftaran berhasil. Akun Anda sedang menunggu verifikasi administrator.');
        });

        $this->assertDatabaseHas('users', [
            'email' => 'donor@example.com',
            'role' => 'donor',
            'status_akun' => 'menunggu',
        ]);
        $this->assertDatabaseHas('donor_profiles', [
            'nama_usaha' => 'Resto Donor Satu',
        ]);
        $user = User::where('email', 'donor@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('Password123!', $user->password));
        $this->assertNotEquals('Password123!', $user->password);
        $this->assertDatabaseHas('verification_documents', [
            'user_id' => $user->id,
            'jenis_dokumen' => 'nib',
            'status' => 'menunggu',
        ]);
    }

    /**
     * TC-2-02: Recipient can register with a legal document.
     */
    public function test_recipient_can_register_with_legal_document(): void
    {
        $fixture = base_path('tests/Browser/fixtures/legalitas.pdf');

        $this->browse(function (Browser $browser) use ($fixture) {
            $browser->visit('/register')
                ->assertSee('Buat akun');
            $browser->script("document.querySelector('input[name=\"role\"][value=\"penerima\"]').click();");
            $browser->type('@register-nama', 'Panti Test Satu')
                ->type('@register-email', 'panti@example.com')
                ->type('@register-no-hp', '081234567891')
                ->type('@register-password', 'Password123!')
                ->type('@register-password-confirmation', 'Password123!')
                ->press('@register-next')
                ->pause(500)
                ->assertVisible('@register-nama-lembaga')
                ->type('@register-nama-lembaga', 'Panti Asuhan Harapan')
                ->type('@register-jenis-lembaga', 'Panti Asuhan')
                ->type('@register-alamat', 'Jl. Harapan No. 2, Bandung')
                ->select('@register-jenis-dokumen', 'nib')
                ->attach('dokumen', $fixture)
                ->press('@register-submit')
                ->waitForText('Pendaftaran berhasil. Akun Anda sedang menunggu verifikasi administrator.')
                ->assertPathIs('/login');
        });

        $this->assertDatabaseHas('users', [
            'email' => 'panti@example.com',
            'role' => 'penerima',
            'status_akun' => 'menunggu',
        ]);
        $this->assertDatabaseHas('recipient_profiles', [
            'nama_lembaga' => 'Panti Asuhan Harapan',
        ]);
        $user = User::where('email', 'panti@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('Password123!', $user->password));
        $this->assertDatabaseHas('verification_documents', [
            'user_id' => $user->id,
            'status' => 'menunggu',
        ]);
    }

    /**
     * TC-2-03: Active users log in through the form and land on their dashboard.
     *
     * Real routes: donor -> /dashboard/donor, recipient -> /dashboard/penerima
     * (not a separate "Recipient catalog" landing page).
     */
    public function test_active_user_can_login_and_is_redirected_by_role(): void
    {
        [$donor] = $this->createDonor('donor-aktif@example.com', 'aktif');
        [$recipient] = $this->createRecipient('penerima-aktif@example.com', 'aktif');

        $this->browse(function (Browser $browser) use ($donor, $recipient) {
            $browser->visit('/login')
                ->type('@login-email', $donor->email)
                ->type('@login-password', 'password')
                ->press('@login-submit')
                ->waitForText('Donor Hub')
                ->assertPathIs('/dashboard/donor')
                ->assertSee('Donor Hub')
                ->assertSee('Selamat datang');

            $browser->click('@logout-button')
                ->waitForLocation('/login');

            $browser->visit('/login')
                ->type('@login-email', $recipient->email)
                ->type('@login-password', 'password')
                ->press('@login-submit')
                ->waitForText('Penerima')
                ->assertPathIs('/dashboard/penerima')
                ->assertSee('Penerima');
        });
    }

    /**
     * TC-2-04: Active donor can log out and loses access to the dashboard.
     */
    public function test_user_can_logout(): void
    {
        [$donor] = $this->createDonor('donor-logout@example.com', 'aktif');

        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::where('email', 'donor-logout@example.com')->firstOrFail())
                ->visit('/dashboard/donor')
                ->assertSee('Donor Hub')
                ->click('@logout-button')
                ->waitForLocation('/login')
                ->visit('/dashboard/donor')
                ->assertPathIs('/login');
        });
    }

    /**
     * TC-2-05: Registration with duplicate email, no document, empty business name fails.
     */
    public function test_registration_is_rejected_for_invalid_input(): void
    {
        $this->createDonor('donor@example.com', 'aktif');

        $this->browse(function (Browser $browser) {
            $browser->visit('/register');
            $browser->script("document.querySelector('input[name=\"role\"][value=\"donor\"]').click();");
            $browser->type('@register-nama', 'Donor Duplikat')
                ->type('@register-email', 'donor@example.com')
                ->type('@register-no-hp', '081234567890')
                ->type('@register-password', 'Password123!')
                ->type('@register-password-confirmation', 'Password123!')
                ->press('@register-next')
                ->pause(500)
                ->assertVisible('@register-nama-usaha')
                ->type('@register-jenis-usaha', 'Restoran')
                ->type('@register-alamat', 'Jl. Duplikat No. 1')
                ->select('@register-jenis-dokumen', 'nib');
            $browser->script("document.querySelector('form').setAttribute('novalidate','novalidate'); document.querySelectorAll('[required]').forEach(function (el) { el.removeAttribute('required'); });");
            $browser->press('@register-submit')
                ->pause(500)
                ->assertPathIs('/register')
                ->assertSee('The email has already been taken.')
                ->type('@register-password', 'Password123!')
                ->type('@register-password-confirmation', 'Password123!')
                ->press('@register-next')
                ->pause(500)
                ->assertVisible('@register-nama-usaha')
                ->assertSee('required');
        });

        $this->assertEquals(1, User::where('email', 'donor@example.com')->count());
    }

    /**
     * TC-2-06: Wrong password is rejected with a generic message.
     */
    public function test_login_is_rejected_for_wrong_credentials(): void
    {
        $this->createDonor('donor-salah@example.com', 'aktif');

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('@login-email', 'donor-salah@example.com')
                ->type('@login-password', 'wrong-password')
                ->press('@login-submit')
                ->waitForText('Email atau kata sandi yang Anda masukkan tidak cocok.')
                ->assertPathIs('/login')
                ->assertSee('Email atau kata sandi yang Anda masukkan tidak cocok.')
                ->visit('/dashboard/donor')
                ->assertPathIs('/login');
        });
    }

    /**
     * TC-2-07: Pending/rejected accounts cannot use role features.
     *
     * ASSUMPTION DIFFERENCE: the spec expects HTTP 403, but the real
     * EnsureAccountIsActive middleware logs the account out and redirects
     * to /login with the Indonesian status message. Assert the real behavior.
     */
    public function test_unverified_or_rejected_account_cannot_access_features(): void
    {
        [$pendingDonor] = $this->createDonor('pending@example.com', 'menunggu');
        [, , $rejectedRecipient] = $this->createRecipientWithStatus('ditolak@example.com', 'ditolak');

        $this->browse(function (Browser $browser) use ($pendingDonor, $rejectedRecipient) {
            $browser->loginAs($pendingDonor)
                ->visit('/dashboard/donor')
                ->assertPathIs('/login')
                ->assertSee('menunggu verifikasi');

            $browser->loginAs($rejectedRecipient)
                ->visit('/penerima/katalog')
                ->assertPathIs('/login')
                ->assertSee('ditolak');
        });
    }

    /**
     * TC-2-08: Guests are redirected to login.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/dashboard/donor')
                ->assertPathIs('/login');
        });
    }

    /**
     * TC-2-09: Active donor cannot open admin or recipient pages (403).
     *
     * Real admin route is /dashboard/admin (not /admin/dashboard).
     */
    public function test_user_cannot_access_other_roles_pages(): void
    {
        [$donor] = $this->createDonor('donor-batas@example.com', 'aktif');

        $this->browse(function (Browser $browser) use ($donor) {
            $browser->loginAs($donor)
                ->visit('/dashboard/admin')
                ->assertSee('403');

            $browser->visit('/penerima/katalog')
                ->assertSee('403');
        });
    }

    /**
     * @return array{0: User}
     */
    private function createDonor(string $email, string $status): array
    {
        $user = User::factory()->create([
            'email' => $email,
            'role' => Role::Donor,
            'status_akun' => $status,
        ]);
        $user->donorProfile()->create([
            'nama_usaha' => 'Usaha '.$email,
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor No. 1',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        return [$user];
    }

    /**
     * @return array{0: User}
     */
    private function createRecipient(string $email, string $status): array
    {
        [$user] = $this->createRecipientWithStatus($email, $status);

        return [$user];
    }

    /**
     * @return array{0: User, 1: mixed, 2: User}
     */
    private function createRecipientWithStatus(string $email, string $status): array
    {
        $user = User::factory()->create([
            'email' => $email,
            'role' => Role::Penerima,
            'status_akun' => $status,
        ]);
        $profile = $user->recipientProfile()->create([
            'nama_lembaga' => 'Lembaga '.$email,
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => 'Jl. Penerima No. 2',
            'lat' => -6.21,
            'lng' => 106.81,
        ]);

        return [$user, $profile, $user];
    }
}
