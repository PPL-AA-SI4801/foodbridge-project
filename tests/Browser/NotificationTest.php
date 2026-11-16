<?php

namespace Tests\Browser;

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
        $user = $this->activeUser();
        $notification = $this->notificationFor($user);

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
        $user = $this->activeUser();
        $notification = $this->notificationFor($user);

        $this->browse(function (Browser $browser) use ($user, $notification) {
            $browser->loginAs($user)
                ->visit('/notifikasi')
                ->assertAttribute('@notification-bell', 'data-unread-count', '1')
                ->click("@open-notification-{$notification->id}")
                ->waitForText('Notifikasi ditandai sudah dibaca.')
                ->assertAttribute('@notification-bell', 'data-unread-count', '0');
        });

        $this->assertTrue($notification->fresh()->is_read);
    }

    public function test_user_can_mark_all_notifications_as_read_from_the_notification_page(): void
    {
        $user = $this->activeUser();
        $first = $this->notificationFor($user);
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

        $this->assertTrue($first->fresh()->is_read);
        $this->assertTrue($second->fresh()->is_read);
    }

    public function test_user_can_mark_a_notification_as_read_from_the_header_dropdown(): void
    {
        $user = $this->activeUser();
        $notification = $this->notificationFor($user);

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

    private function activeUser(): User
    {
        return User::factory()->create([
            'role' => Role::Donor,
            'status_akun' => 'aktif',
        ]);
    }

    private function notificationFor(User $user, array $attributes = []): InAppNotification
    {
        return InAppNotification::create(array_merge([
            'user_id' => $user->id,
            'judul' => 'Klaim baru menunggu persetujuan',
            'pesan' => 'Ada penerima yang mengajukan klaim untuk postingan Anda.',
            'tipe' => 'claim',
        ], $attributes));
    }
}
