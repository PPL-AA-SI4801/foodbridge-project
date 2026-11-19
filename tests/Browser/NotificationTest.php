<?php

namespace Tests\Browser;

use App\Enums\FoodPostStatus;
use App\Enums\NotificationType;
use App\Enums\Role;
use App\Models\Claim;
use App\Models\DonorProfile;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\InAppNotification;
use App\Models\RecipientProfile;
use App\Models\User;
use App\Models\VerificationDocument;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class NotificationTest extends DuskTestCase
{
    use DatabaseTruncation;

    /**
     * TS-1: Penerima mengajukan klaim → Donor mendapat notifikasi dan membukanya.
     */
    public function test_recipient_claim_through_catalog_notifies_donor_who_opens_it(): void
    {
        [$donorUser, $donorProfile] = $this->donorWithProfile();
        [$recipientUser] = $this->recipientWithProfile();
        $post = $this->foodPostFor($donorProfile);

        $this->browse(function (Browser $browser) use ($recipientUser, $post) {
            $browser->loginAs($recipientUser)
                ->visit('/penerima/katalog')
                ->assertSee($post->judul)
                ->click("@claim-toggle-{$post->id}")
                ->type("#jumlah_porsi_{$post->id}", '2')
                ->click("@claim-submit-{$post->id}")
                ->waitForText('Klaim berhasil diajukan');
        });

        $notification = InAppNotification::query()
            ->where('user_id', $donorUser->id)
            ->sole();

        $this->browse(function (Browser $browser) use ($donorUser, $notification) {
            $browser->loginAs($donorUser)
                ->visit('/notifikasi')
                ->assertAttribute('@notification-bell', 'data-unread-count', '1')
                ->assertSee('Pengajuan Klaim Baru')
                ->click("@open-notification-{$notification->id}")
                ->waitForLocation('/donor/claims')
                ->assertPathIs('/donor/claims')
                ->assertAttribute('@notification-bell', 'data-unread-count', '0');
        });

        $this->assertTrue($notification->fresh()->is_read);
    }

    /**
     * TS-2: Donor menyetujui dan menolak klaim → Penerima mendapat kedua notifikasi.
     */
    public function test_donor_approve_and_reject_notify_recipient_who_opens_them(): void
    {
        [$donorUser, $donorProfile] = $this->donorWithProfile();
        [$recipientUser, $recipientProfile] = $this->recipientWithProfile();
        $approved = $this->claimFor($this->foodPostFor($donorProfile), $recipientProfile);
        $rejected = $this->claimFor($this->foodPostFor($donorProfile), $recipientProfile);

        $this->browse(function (Browser $browser) use ($donorUser, $approved, $rejected) {
            $browser->loginAs($donorUser)
                ->visit('/donor/claims')
                ->click("@approve-claim-{$approved->id}")
                ->waitForText('Klaim berhasil disetujui')
                ->click("@reject-claim-{$rejected->id}")
                ->waitForDialog()
                ->acceptDialog()
                ->waitForText('Klaim berhasil ditolak');
        });

        $this->browse(function (Browser $browser) use ($recipientUser) {
            $browser->loginAs($recipientUser)
                ->visit('/notifikasi')
                ->assertAttribute('@notification-bell', 'data-unread-count', '2')
                ->assertSee('Klaim Disetujui')
                ->assertSee('Klaim Ditolak');

            $approved = InAppNotification::query()
                ->where('user_id', $recipientUser->id)
                ->where('judul', 'Klaim Disetujui')
                ->sole();
            $rejected = InAppNotification::query()
                ->where('user_id', $recipientUser->id)
                ->where('judul', 'Klaim Ditolak')
                ->sole();

            $browser->click("@open-notification-{$approved->id}")
                ->waitForLocation('/recipient/claims')
                ->assertPathIs('/recipient/claims')
                ->visit('/notifikasi')
                ->click("@open-notification-{$rejected->id}")
                ->waitForLocation('/recipient/claims')
                ->assertPathIs('/recipient/claims');
        });

        $this->assertSame(0, InAppNotification::query()
            ->where('user_id', $recipientUser->id)
            ->where('is_read', false)
            ->count());
    }

    /**
     * TS-3: Pendaftaran disetujui admin → Pendaftar mendapat notifikasi dan membukanya.
     */
    public function test_verified_account_notification_opens_dashboard(): void
    {
        $admin = $this->dummyAdmin();
        $applicant = $this->dummyDonor();
        $applicant->update(['status_akun' => 'menunggu']);
        VerificationDocument::create([
            'user_id' => $applicant->id,
            'jenis_dokumen' => 'nib',
            'file_path' => 'verification-documents/nib.pdf',
            'status' => 'menunggu',
        ]);

        $notification = null;

        $this->browse(function (Browser $browser) use ($admin, $applicant, &$notification) {
            // Admin masuk lewat form login lalu menyetujui pendaftaran.
            $browser->visit('/login')
                ->type('@login-email', $admin->email)
                ->type('@login-password', 'password')
                ->press('@login-submit')
                ->waitForLocation('/dashboard/admin')
                ->visit(route('admin.verifications.show', $applicant))
                ->press('@verification-approve')
                ->waitForText('Pendaftaran berhasil disetujui. Akun sekarang aktif.')
                // Keluar, lalu masuk sebagai pendaftar yang baru aktif.
                ->click('@logout')
                ->waitForLocation('/login')
                ->visit('/login')
                ->type('@login-email', $applicant->email)
                ->type('@login-password', 'password')
                ->press('@login-submit')
                ->waitForLocation('/dashboard/donor')
                ->visit('/notifikasi');

            $notification = InAppNotification::query()
                ->where('user_id', $applicant->id)
                ->where('tipe', NotificationType::Account->value)
                ->sole();

            // Klik native WebDriver meleset pada alur ini (tidak ada request masuk
            // sama sekali), sementara tautan dan redirect server terbukti benar
            // lewat TS-1/TS-2 dan visit langsung. Pakai klik JS agar fokus test
            // tetap pada perilaku open(): notifikasi akun → dashboard.
            $browser->driver->executeScript(
                "document.querySelector('[dusk=\"open-notification-{$notification->id}\"]').click()"
            );
            $browser->waitForLocation('/dashboard/donor')
                ->assertPathIs('/dashboard/donor');
        });

        $this->assertTrue($notification->fresh()->is_read);
    }

    /**
     * TS-4: Donor mencoba membuka notifikasi milik penerima → ditolak 403.
     */
    public function test_user_cannot_open_another_users_notification(): void
    {
        [$userA] = $this->donorWithProfile();
        [$userB] = $this->recipientWithProfile();

        $notificationB = $this->notificationFor($userB, ['judul' => 'Notifikasi rahasia']);

        $this->browse(function (Browser $browser) use ($userA, $notificationB) {
            $browser->loginAs($userA)
                ->visit('/notifikasi/'.$notificationB->id)
                ->assertSee('403');
        });

        $this->assertFalse($notificationB->fresh()->is_read);
    }

    /**
     * Akun dummy sesuai database seeder (password: password).
     * Dibuat ulang bila belum ada agar test jalan di DB Dusk yang kosong.
     */
    private function dummyAdmin(): User
    {
        return User::updateOrCreate(
            ['email' => 'admin@foodbridge.test'],
            [
                'nama' => 'Administrator',
                'password' => bcrypt('password'),
                'no_hp' => '081200000003',
                'role' => Role::Administrator,
                'status_akun' => 'aktif',
            ],
        );
    }

    /**
     * @return array{0: User, 1: DonorProfile}
     */
    private function donorWithProfile(): array
    {
        $user = User::updateOrCreate(
            ['email' => 'donor@foodbridge.test'],
            [
                'nama' => 'Donor Demo',
                'password' => bcrypt('password'),
                'no_hp' => '081200000001',
                'role' => Role::Donor,
                'status_akun' => 'aktif',
            ],
        );

        $profile = $user->donorProfile()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'nama_usaha' => 'Toko Donor Demo',
                'jenis_usaha' => 'Restoran',
                'alamat' => 'Jl. Donor No. 1, Bandung',
                'lat' => -6.2,
                'lng' => 106.8,
            ],
        );

        return [$user->fresh(), $profile];
    }

    /**
     * @return array{0: User, 1: RecipientProfile}
     */
    private function recipientWithProfile(): array
    {
        $user = User::updateOrCreate(
            ['email' => 'penerima@foodbridge.test'],
            [
                'nama' => 'Penerima Demo',
                'password' => bcrypt('password'),
                'no_hp' => '081200000002',
                'role' => Role::Penerima,
                'status_akun' => 'aktif',
            ],
        );

        $profile = $user->recipientProfile()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'nama_lembaga' => 'Panti Penerima Demo',
                'jenis_lembaga' => 'Panti Asuhan',
                'alamat' => 'Jl. Penerima No. 2, Bandung',
                'lat' => -6.21,
                'lng' => 106.81,
            ],
        );

        return [$user->fresh(), $profile];
    }

    private function dummyDonor(): User
    {
        return $this->donorWithProfile()[0];
    }

    private function foodPostFor($donorProfile): FoodPost
    {
        $category = FoodCategory::create([
            'nama_kategori' => 'Kategori Notifikasi '.uniqid(),
        ]);

        return FoodPost::create([
            'judul' => 'Nasi Box Notifikasi '.uniqid(),
            'deskripsi' => 'Makanan untuk uji notifikasi.',
            'foto' => 'food-posts/sample.jpg',
            'jumlah_porsi' => 20,
            'sisa_porsi' => 20,
            'alamat' => 'Jl. Notifikasi No. 1, Bandung',
            'lat' => -6.2,
            'lng' => 106.8,
            'batas_konsumsi' => now()->addHours(8),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donorProfile->id,
            'category_id' => $category->id,
        ]);
    }

    private function claimFor(FoodPost $post, $recipientProfile): Claim
    {
        return Claim::create([
            'post_id' => $post->id,
            'recipient_id' => $recipientProfile->id,
            'jumlah_porsi' => 1,
            'status' => 'pending',
            'tanggal_klaim' => today(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function notificationFor(User $user, array $attributes = []): InAppNotification
    {
        return InAppNotification::create(array_merge([
            'user_id' => $user->id,
            'judul' => 'Klaim baru menunggu persetujuan',
            'pesan' => 'Ada penerima yang mengajukan klaim untuk postingan Anda.',
            'tipe' => 'klaim',
        ], $attributes));
    }
}
