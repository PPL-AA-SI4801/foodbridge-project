<?php

namespace Tests\Browser;

use App\Enums\Role;
use App\Models\User;
use App\Models\VerificationDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AccountVerificationTest extends DuskTestCase
{
    private static int $emailSeq = 0;

    /**
     * Generate a unique email per run so tests can run repeatedly
     * against the persistent Dusk database without duplicates.
     */
    private function uniqueEmail(string $prefix): string
    {
        self::$emailSeq++;

        return sprintf('%s-%d-%d-%s@example.com', strtolower($prefix), time(), self::$emailSeq, strtolower(Str::random(4)));
    }

    /**
     * Generate a unique business/organization name per run, since
     * nama_usaha is unique at the database level and both names are
     * validated unique on register and profile update.
     */
    private function uniqueName(string $base): string
    {
        return sprintf('%s %d-%s', $base, time(), strtolower(Str::random(4)));
    }

    /**
     * TC-3-01: Administrator can view pending registrations.
     *
     * Real user flow: donor + penerima register via /register (UI + upload
     * dokumen), then admin opens /admin/verifications and sees both.
     */
    public function test_administrator_can_view_pending_registrations(): void
    {
        $admin = $this->createAdmin();
        $fixture = base_path('tests/Browser/fixtures/legalitas.pdf');
        $donorEmail = $this->uniqueEmail('donor-pending');
        $namaDonor = $this->uniqueName('Resto Donor Satu');
        $recipientEmail = $this->uniqueEmail('penerima-pending');
        $namaPenerima = $this->uniqueName('Panti Penerima Satu');

        $this->browse(function (Browser $browser) use ($admin, $fixture, $donorEmail, $namaDonor, $recipientEmail, $namaPenerima) {
            // 1. Donor registers via UI.
            $browser->visit('/register')
                ->assertSee('Buat akun')
                ->type('@register-nama', 'Donor Pending Satu')
                ->type('@register-email', $donorEmail)
                ->type('@register-no-hp', '081234567890')
                ->type('@register-password', 'Password123!')
                ->type('@register-password-confirmation', 'Password123!')
                ->press('@register-next')
                ->pause(500)
                ->assertVisible('@register-nama-usaha')
                ->type('@register-nama-usaha', $namaDonor)
                ->type('@register-jenis-usaha', 'Restoran')
                ->type('@register-alamat', 'Jl. Donor No. 1')
                ->select('@register-jenis-dokumen', 'nib')
                ->attach('dokumen', $fixture)
                ->press('@register-submit')
                ->waitForText('Pendaftaran berhasil. Lengkapi dokumen Anda agar proses verifikasi bisa dilanjutkan.')
                ->assertPathIs('/login')
                ->assertSee('Status verifikasi akun');

            // Kartu status persisten (commit verification card): refresh tetap tampil.
            $browser->refresh()
                ->assertPathIs('/login')
                ->assertSee('Status verifikasi akun');

            // 2. Penerima registers via UI.
            $browser->visit('/register')
                ->assertSee('Buat akun');
            $browser->script("document.querySelector('input[name=\"role\"][value=\"penerima\"]').click();");
            $browser->type('@register-nama', 'Penerima Pending Satu')
                ->type('@register-email', $recipientEmail)
                ->type('@register-no-hp', '081234567891')
                ->type('@register-password', 'Password123!')
                ->type('@register-password-confirmation', 'Password123!')
                ->press('@register-next')
                ->pause(500)
                ->assertVisible('@register-nama-lembaga')
                ->type('@register-nama-lembaga', $namaPenerima)
                ->type('@register-jenis-lembaga', 'Panti Asuhan')
                ->type('@register-alamat', 'Jl. Penerima No. 2')
                ->select('@register-jenis-dokumen', 'nib')
                ->attach('dokumen', $fixture)
                ->press('@register-submit')
                ->waitForText('Pendaftaran berhasil. Lengkapi dokumen Anda agar proses verifikasi bisa dilanjutkan.')
                ->assertPathIs('/login')
                ->assertSee('Status verifikasi akun');

            // 3. Admin sees both pending registrations.
            // Catatan: loginAs tidak menghapus session pending_verification_email,
            // tapi kartu hanya tampil di /login (guest), jadi antrean admin aman.
            $browser->loginAs($admin)
                ->visit('/admin/verifications')
                ->assertSee('Verifikasi Akun')
                ->assertSee($namaDonor)
                ->assertSee($namaPenerima)
                ->assertSee($donorEmail)
                ->assertSee($recipientEmail)
                ->assertSee('Donor')
                ->assertSee('Penerima');
        });

        $this->assertDatabaseHas('users', [
            'email' => $donorEmail,
            'status_akun' => 'menunggu',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => $recipientEmail,
            'status_akun' => 'menunggu',
        ]);
    }

    /**
     * TC-3-02: Filter registrations by role and status.
     *
     * ASSUMPTION DIFFERENCE: the real queue page has no role/status filter
     * controls; VerificationController::index only returns pendingVerification()
     * (donor/penerima with status menunggu). The test therefore verifies the
     * implicit status filter: pending accounts are listed, active ones are not.
     */
    public function test_administrator_can_filter_registrations_by_role_and_status(): void
    {
        $admin = $this->createAdmin();
        $tag = time().'-'.strtolower(Str::random(4));
        $pendingDonor = $this->createPendingDonor($this->uniqueEmail('pending-donor'), 'Donor Pending Filter '.$tag);
        $pendingRecipient = $this->createPendingRecipient($this->uniqueEmail('pending-penerima'), 'Penerima Pending Filter '.$tag);
        $activeDonorName = 'Donor Aktif Filter '.$tag;
        $this->createActiveDonor($this->uniqueEmail('active-donor'), $activeDonorName);

        $this->browse(function (Browser $browser) use ($admin, $tag, $activeDonorName) {
            $browser->loginAs($admin)
                ->visit('/admin/verifications')
                ->assertSee('Verifikasi Akun')
                ->assertSee('Penerima Pending Filter '.$tag)
                ->assertSee('Donor Pending Filter '.$tag)
                ->assertDontSee($activeDonorName);
        });
    }

    /**
     * TC-3-03: Administrator can approve a registration.
     *
     * Real app stays on the detail page with a success message
     * (not back on the verification list) and has no confirm dialog.
     */
    public function test_administrator_can_approve_a_registration(): void
    {
        $admin = $this->createAdmin();
        $email = $this->uniqueEmail('approve-me');
        $namaUsaha = $this->uniqueName('Resto Setuju');
        $donor = $this->createPendingDonor($email, $namaUsaha);

        $this->browse(function (Browser $browser) use ($admin, $donor, $namaUsaha) {
            $browser->loginAs($admin)
                ->visit('/admin/verifications/'.$donor->id)
                ->assertSee('Detail Verifikasi')
                ->assertSee($namaUsaha)
                ->press('@verification-approve')
                ->waitForText('Pendaftaran berhasil disetujui. Akun sekarang aktif.')
                ->assertPathIs('/admin/verifications/'.$donor->id)
                ->assertSee('Pendaftaran berhasil disetujui. Akun sekarang aktif.');

            $browser->logout()
                ->visit('/login')
                ->type('@login-email', $donor->email)
                ->type('@login-password', 'password')
                ->press('@login-submit')
                ->waitForText('Donor Hub')
                ->assertPathIs('/dashboard/donor')
                ->assertSee('Donor Hub');
        });

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'status_akun' => 'aktif',
        ]);
    }

    /**
     * TC-3-04: Administrator can reject a registration with a reason.
     */
    public function test_administrator_can_reject_a_registration(): void
    {
        $admin = $this->createAdmin();
        $email = $this->uniqueEmail('reject-me');
        $recipient = $this->createPendingRecipient($email, $this->uniqueName('Panti Tolak'));

        $this->browse(function (Browser $browser) use ($admin, $recipient) {
            $browser->loginAs($admin)
                ->visit('/admin/verifications/'.$recipient->id)
                ->assertSee('Detail Verifikasi')
                ->type('@verification-catatan', 'Dokumen tidak jelas, mohon unggah ulang.')
                ->press('@verification-reject')
                ->waitForText('Pendaftaran berhasil ditolak.')
                ->assertPathIs('/admin/verifications/'.$recipient->id)
                ->assertSee('Pendaftaran berhasil ditolak.');
        });

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'status_akun' => 'ditolak',
        ]);
        $user = User::where('email', $email)->firstOrFail();
        $this->assertDatabaseHas('verification_documents', [
            'user_id' => $user->id,
            'status' => 'ditolak',
        ]);
        $this->assertNotNull(User::where('email', $email)->first());
        $this->assertTrue(VerificationDocument::where('user_id', $user->id)->exists());
    }

    /**
     * TC-3-04b: Administrator can request document completeness and the applicant can re-upload.
     *
     * Alur yang diuji: authenticated flow (/lengkapi-dokumen via
     * DocumentCompletenessController). Donor dibuat via factory sehingga
     * tidak ada session pending_verification_email — kartu guest di /login
     * (VerificationStatusController::store) tidak muncul, login via form
     * tetap terautentikasi lalu EnsureAccountIsActive mengarahkan ke
     * /lengkapi-dokumen. Guest flow (upload inline di kartu login) hanya
     * aktif bila session pending_verification_email masih ada (habis register via UI).
     */
    public function test_administrator_can_request_document_completeness_and_applicant_can_reupload(): void
    {
        $admin = $this->createAdmin();
        $namaUsaha = $this->uniqueName('Resto Lengkapi');
        $donor = $this->createPendingDonor($this->uniqueEmail('lengkapi'), $namaUsaha);
        $document = $donor->verificationDocuments()->firstOrFail();
        $fixture = base_path('tests/Browser/fixtures/legalitas.pdf');

        $this->browse(function (Browser $browser) use ($admin, $donor, $document, $fixture, $namaUsaha) {
            $browser->loginAs($admin)
                ->visit('/admin/verifications/'.$donor->id)
                ->type('@verification-request-documents-catatan', 'NIB kurang jelas, mohon unggah ulang.')
                ->press('@verification-request-documents')
                ->waitForText('Permintaan kelengkapan dokumen berhasil dikirim.')
                ->assertSee('Menunggu kelengkapan dokumen');

            $browser->logout()
                ->visit('/login')
                ->type('@login-email', $donor->email)
                ->type('@login-password', 'password')
                ->press('@login-submit')
                ->waitForText('Lengkapi Dokumen')
                ->assertPathIs('/lengkapi-dokumen')
                ->assertSee('NIB kurang jelas, mohon unggah ulang.')
                ->attach('#dokumen-'.$document->id, $fixture)
                ->press('@completeness-submit-'.$document->id)
                ->waitForText('Dokumen perbaikan berhasil diunggah. Akun Anda kembali menunggu verifikasi administrator.')
                ->assertPathIs('/login');

            $browser->loginAs($admin)
                ->visit('/admin/verifications/'.$donor->id)
                ->click('button[aria-label="Notifikasi"]')
                ->waitFor('#notification-menu-dropdown')
                ->assertSee('Kelengkapan Dokumen Dikirim')
                ->assertSee($namaUsaha);
        });

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'judul' => 'Kelengkapan Dokumen Dikirim',
            'tipe' => 'akun',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $donor->id,
            'status_akun' => 'menunggu',
        ]);
        $this->assertDatabaseHas('verification_documents', [
            'id' => $document->id,
            'status' => 'ditolak',
        ]);
        $this->assertDatabaseHas('verification_documents', [
            'user_id' => $donor->id,
            'jenis_dokumen' => 'nib',
            'status' => 'menunggu',
        ]);

        $replacement = $donor->verificationDocuments()
            ->where('status', 'menunggu')
            ->latest('id')
            ->first();

        if ($replacement) {
            Storage::disk('local')->delete($replacement->file_path);
        }
    }

    /**
     * TC-3-05: A decision on an already-decided registration is refused.
     */
    public function test_administrator_cannot_decide_an_already_decided_registration(): void
    {
        $admin = $this->createAdmin();
        $email = $this->uniqueEmail('race');
        $donor = $this->createPendingDonor($email, $this->uniqueName('Resto Race'));

        $this->browse(function (Browser $browser) use ($admin, $donor) {
            $browser->loginAs($admin)
                ->visit('/admin/verifications/'.$donor->id)
                ->assertSee('Detail Verifikasi');

            $donor->refresh();
            $donor->update(['status_akun' => 'aktif']);

            $browser->type('@verification-catatan', 'Terlambat menolak.')
                ->press('@verification-reject')
                ->waitForText('Pendaftaran ini sudah diproses.')
                ->assertSee('Pendaftaran ini sudah diproses.');
        });

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'status_akun' => 'aktif',
        ]);
    }

    /**
     * TC-3-06: Pending/rejected accounts cannot use role features.
     *
     * ASSUMPTION DIFFERENCE: real behavior is logout + redirect to /login
     * with the Indonesian status message (see EnsureAccountIsActive),
     * not an HTTP 403 page.
     */
    public function test_unverified_or_rejected_accounts_cannot_access_features(): void
    {
        $pendingDonor = $this->createPendingDonor($this->uniqueEmail('pending-fitur'), $this->uniqueName('Resto Pending Fitur'));
        $rejectedRecipient = $this->createPendingRecipient($this->uniqueEmail('rejected-fitur'), $this->uniqueName('Panti Ditolak Fitur'));
        $rejectedRecipient->update(['status_akun' => 'ditolak']);

        $this->browse(function (Browser $browser) use ($pendingDonor, $rejectedRecipient) {
            $browser->loginAs($pendingDonor)
                ->visit('/dashboard/donor')
                ->assertPathIs('/login')
                ->assertSee('menunggu verifikasi');

            $browser->loginAs($rejectedRecipient->refresh())
                ->visit('/penerima/katalog')
                ->assertPathIs('/login')
                ->assertSee('ditolak');
        });
    }

    /**
     * TC-3-07: Non-administrators cannot open verification pages.
     */
    public function test_non_administrators_cannot_access_account_verification(): void
    {
        $donor = $this->createActiveDonor($this->uniqueEmail('donor-biasa'), $this->uniqueName('Resto Biasa'));

        $this->browse(function (Browser $browser) use ($donor) {
            $browser->loginAs($donor)
                ->visit('/admin/verifications')
                ->assertSee('403');
        });
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'email' => $this->uniqueEmail('admin'),
            'role' => Role::Administrator,
            'status_akun' => 'aktif',
        ]);
    }

    private function createPendingDonor(string $email, string $namaUsaha): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'role' => Role::Donor,
            'status_akun' => 'menunggu',
        ]);
        $user->donorProfile()->create([
            'nama_usaha' => $namaUsaha,
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor No. 1',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);
        $user->verificationDocuments()->create([
            'jenis_dokumen' => 'nib',
            'file_path' => 'verification-documents/'.$user->id.'/nib.pdf',
            'status' => 'menunggu',
        ]);

        return $user->refresh();
    }

    private function createPendingRecipient(string $email, string $namaLembaga): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'role' => Role::Penerima,
            'status_akun' => 'menunggu',
        ]);
        $user->recipientProfile()->create([
            'nama_lembaga' => $namaLembaga,
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => 'Jl. Penerima No. 2',
            'lat' => -6.21,
            'lng' => 106.81,
        ]);
        $user->verificationDocuments()->create([
            'jenis_dokumen' => 'nib',
            'file_path' => 'verification-documents/'.$user->id.'/nib.pdf',
            'status' => 'menunggu',
        ]);

        return $user->refresh();
    }

    private function createActiveDonor(string $email, string $namaUsaha): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'role' => Role::Donor,
            'status_akun' => 'aktif',
        ]);
        $user->donorProfile()->create([
            'nama_usaha' => $namaUsaha,
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Aktif No. 3',
            'lat' => -6.22,
            'lng' => 106.82,
        ]);

        return $user->refresh();
    }
}
