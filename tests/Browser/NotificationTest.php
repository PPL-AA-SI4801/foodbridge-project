<?php

namespace Tests\Browser;

use App\Enums\NotificationType;
use App\Enums\Role;
use App\Models\InAppNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class NotificationTest extends DuskTestCase
{
    use DatabaseTruncation;

    public function test_user_can_mark_a_notification_as_read_from_the_notification_page(): void
    {
        [$user, $notification] = $this->registeredUserWithNotification();

        $this->browse(function (Browser $browser) use ($user, $notification) {
            $browser->loginAs($user)
                ->visit('/notifikasi')
                ->assertSee('Notifikasi')
                ->assertSee('Klaim baru menunggu persetujuan')
                ->assertSee('1 belum dibaca')
                ->assertSee('Tandai semua dibaca')
                ->click("@mark-read-{$notification->id}")
                ->waitForText('Notifikasi ditandai sudah dibaca.')
                ->assertSee('Semua sudah dibaca')
                ->assertDontSee('1 belum dibaca');
        });

        $this->assertTrue($notification->fresh()->is_read);
    }

    public function test_user_can_open_a_notification_and_the_bell_count_decreases(): void
    {
        [$user, $notification] = $this->registeredUserWithNotification();

        $this->browse(function (Browser $browser) use ($user, $notification) {
            $browser->loginAs($user)
                ->visit('/notifikasi')
                ->assertAttribute('@notification-bell', 'data-unread-count', '1')
                ->click("@open-notification-{$notification->id}")
                ->waitFor('@notification-popup')
                ->assertSeeIn('@notification-popup', 'Klaim baru menunggu persetujuan')
                ->assertSeeIn('@notification-popup', 'Ada penerima yang mengajukan klaim untuk postingan Anda.')
                ->waitForText('Notifikasi ditandai sudah dibaca.')
                ->assertAttribute('@notification-bell', 'data-unread-count', '0')
                ->click('@close-notification-popup');
        });

        $this->assertTrue($notification->fresh()->is_read);
    }

    public function test_user_is_redirected_to_the_related_page_when_a_notification_has_an_action_target(): void
    {
        [$user, $notification] = $this->registeredUserWithNotification([
            'action_url' => '/profile',
        ]);

        $this->browse(function (Browser $browser) use ($user, $notification) {
            $browser->loginAs($user)
                ->visit('/notifikasi')
                ->click("@open-notification-{$notification->id}")
                ->waitForLocation('/profile')
                ->assertPathIs('/profile')
                ->assertSee('Profil');
        });

        $this->assertTrue($notification->fresh()->is_read);
    }

    public function test_user_can_mark_all_notifications_as_read_from_the_notification_page(): void
    {
        [$user] = $this->registeredUserWithNotification();
        $second = $this->notificationFor($user, ['judul' => 'Klaim kedua menunggu persetujuan']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/notifikasi')
                ->assertSee('2 belum dibaca')
                ->click('@mark-all-read')
                ->waitForText('Semua notifikasi ditandai sudah dibaca.')
                ->assertSee('Semua sudah dibaca')
                ->assertDontSee('Tandai semua dibaca');
        });

        $this->assertTrue($second->fresh()->is_read);
    }

    public function test_user_can_mark_a_notification_as_read_from_the_header_dropdown(): void
    {
        [$user, $notification] = $this->registeredUserWithNotification();

        $this->browse(function (Browser $browser) use ($user, $notification) {
            $browser->loginAs($user)
                ->visit('/notifikasi')
                ->click('button[aria-label="Notifikasi"]')
                ->waitFor('#notification-menu-dropdown')
                ->click("@header-mark-read-{$notification->id}")
                ->waitForText('Notifikasi ditandai sudah dibaca.')
                ->assertSee('Semua sudah dibaca');
        });

        $this->assertTrue($notification->fresh()->is_read);
    }

    /**
     * @return array{0: User, 1: InAppNotification}
     */
    private function registeredUserWithNotification(array $attributes = []): array
    {
        $email = null;

        $this->browse(function (Browser $browser) use (&$email) {
            $email = $this->registerDonor($browser);
            $this->approveDonor($browser, $email);
        });

        $user = User::where('email', $email)->firstOrFail();

        // Keep this UI test focused on the notification created for the scenario;
        // the account verification flow legitimately creates its own notification.
        InAppNotification::where('user_id', $user->id)
            ->where('tipe', NotificationType::Account->value)
            ->delete();

        $notification = $this->notificationFor($user, $attributes);

        return [$user, $notification];
    }

    private function registerDonor(Browser $browser): string
    {
        $email = 'donor-notification-'.uniqid().'@example.com';
        $fixture = base_path('tests/Browser/fixtures/legalitas.pdf');

        $browser->visit('/register')
            ->type('@register-nama', 'Donor Notifikasi')
            ->type('@register-email', $email)
            ->type('@register-no-hp', '081234567890')
            ->type('@register-password', 'Password123!')
            ->type('@register-password-confirmation', 'Password123!')
            ->press('@register-next')
            ->pause(500)
            ->assertVisible('@register-nama-usaha')
            ->type('@register-nama-usaha', 'Resto Notifikasi')
            ->type('@register-jenis-usaha', 'Restoran')
            ->type('@register-alamat', 'Jl. Notifikasi No. 1, Bandung')
            ->select('@register-jenis-dokumen', 'nib')
            ->attach('dokumen', $fixture)
            ->press('@register-submit')
            ->waitForText('Pendaftaran berhasil. Akun Anda sedang menunggu verifikasi administrator.')
            ->assertPathIs('/login');

        return $email;
    }

    private function approveDonor(Browser $browser, string $email): void
    {
        $admin = User::factory()->create([
            'email' => 'admin-notification-'.uniqid().'@example.com',
            'role' => Role::Administrator,
            'status_akun' => 'aktif',
            'nama' => 'Admin Notifikasi',
            'password' => bcrypt('password'),
            'no_hp' => '081234567891',
        ]);

        $user = User::where('email', $email)->firstOrFail();

        $browser->loginAs($admin)
            ->visit(route('admin.verifications.show', $user))
            ->press('@verification-approve')
            ->waitForText('Pendaftaran berhasil disetujui. Akun sekarang aktif.');
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
