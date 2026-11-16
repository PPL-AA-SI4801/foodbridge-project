<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\InAppNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class NotificationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('notifications.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_only_see_their_own_notifications(): void
    {
        $donor = $this->activeUser(Role::Donor);
        $otherUser = $this->activeUser(Role::Penerima);

        $this->notificationFor($donor, [
            'judul' => 'Klaim baru menunggu persetujuan',
            'pesan' => 'Ada penerima yang mengajukan klaim untuk postingan Anda.',
            'tipe' => 'claim',
        ]);

        $this->notificationFor($otherUser, [
            'judul' => 'Notifikasi rahasia pengguna lain',
            'pesan' => 'Notifikasi ini bukan milik akun yang sedang masuk.',
            'tipe' => 'claim',
        ]);

        $this->actingAs($donor)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Klaim baru menunggu persetujuan')
            ->assertSee('Klaim Makanan')
            ->assertSee('Lihat semua notifikasi')
            ->assertDontSee('Notifikasi rahasia pengguna lain');
    }

    public function test_unread_filter_only_shows_unread_notifications(): void
    {
        $user = $this->activeUser(Role::Donor);

        $this->notificationFor($user, [
            'judul' => 'Notifikasi yang belum dibaca',
            'pesan' => 'Notifikasi ini masih baru.',
            'tipe' => 'claim',
            'is_read' => false,
        ]);

        $this->notificationFor($user, [
            'judul' => 'Notifikasi yang sudah dibaca',
            'pesan' => 'Notifikasi ini sudah dibuka sebelumnya.',
            'tipe' => 'claim',
            'is_read' => true,
        ]);

        $unreadResponse = $this->actingAs($user)
            ->get(route('notifications.index', ['filter' => 'unread']));

        $unreadResponse
            ->assertOk()
            ->assertSee('Notifikasi yang belum dibaca')
            ->assertSee('1 belum dibaca');

        $this->assertStringNotContainsString(
            'Notifikasi yang sudah dibaca',
            $this->mainContent($unreadResponse),
        );

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Notifikasi yang belum dibaca')
            ->assertSee('Notifikasi yang sudah dibaca');
    }

    public function test_unread_filter_shows_an_empty_state_when_everything_is_read(): void
    {
        $user = $this->activeUser(Role::Penerima);

        $this->notificationFor($user, [
            'judul' => 'Notifikasi lama',
            'pesan' => 'Sudah dibaca.',
            'tipe' => 'akun',
            'is_read' => true,
        ]);

        $response = $this->actingAs($user)
            ->get(route('notifications.index', ['filter' => 'unread']));

        $response
            ->assertOk()
            ->assertSee('Tidak ada notifikasi yang belum dibaca')
            ->assertSee('Lihat semua notifikasi');

        $this->assertStringNotContainsString('Notifikasi lama', $this->mainContent($response));
    }

    public function test_empty_state_is_shown_when_the_account_has_no_notifications(): void
    {
        $user = $this->activeUser(Role::Donor);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Belum ada notifikasi')
            ->assertSee('Kembali ke dashboard');
    }

    public function test_guests_cannot_mark_notifications_as_read(): void
    {
        $user = $this->activeUser(Role::Donor);
        $notification = $this->notificationFor($user);

        $this->patch(route('notifications.read', $notification))
            ->assertRedirect(route('login'));

        $this->assertFalse($notification->fresh()->is_read);
    }

    public function test_a_user_can_mark_their_own_notification_as_read(): void
    {
        $user = $this->activeUser(Role::Donor);
        $notification = $this->notificationFor($user);

        $this->actingAs($user)
            ->from(route('notifications.index'))
            ->patch(route('notifications.read', $notification))
            ->assertRedirect(route('notifications.index'))
            ->assertSessionHas('status', 'Notifikasi ditandai sudah dibaca.');

        $this->assertTrue($notification->fresh()->is_read);
    }

    public function test_a_user_cannot_mark_another_users_notification_as_read(): void
    {
        $user = $this->activeUser(Role::Donor);
        $otherUser = $this->activeUser(Role::Penerima);
        $notification = $this->notificationFor($otherUser);

        $this->actingAs($user)
            ->from(route('notifications.index'))
            ->patch(route('notifications.read', $notification))
            ->assertForbidden();

        $this->assertFalse($notification->fresh()->is_read);
    }

    public function test_a_user_can_mark_all_of_their_notifications_as_read(): void
    {
        $user = $this->activeUser(Role::Donor);
        $otherUser = $this->activeUser(Role::Penerima);

        $first = $this->notificationFor($user);
        $second = $this->notificationFor($user);
        $alreadyRead = $this->notificationFor($user, ['is_read' => true]);
        $otherNotification = $this->notificationFor($otherUser);

        $this->actingAs($user)
            ->from(route('notifications.index', ['filter' => 'unread']))
            ->patch(route('notifications.read-all'))
            ->assertRedirect(route('notifications.index', ['filter' => 'unread']))
            ->assertSessionHas('status', 'Semua notifikasi ditandai sudah dibaca.');

        $this->assertTrue($first->fresh()->is_read);
        $this->assertTrue($second->fresh()->is_read);
        $this->assertTrue($alreadyRead->fresh()->is_read);
        $this->assertFalse($otherNotification->fresh()->is_read);
    }

    public function test_the_page_shows_the_mark_as_read_actions(): void
    {
        $user = $this->activeUser(Role::Donor);

        $this->notificationFor($user, [
            'judul' => 'Klaim baru menunggu persetujuan',
            'pesan' => 'Ada penerima yang mengajukan klaim untuk postingan Anda.',
            'tipe' => 'claim',
        ]);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Tandai semua dibaca')
            ->assertSee('Tandai dibaca');
    }

    public function test_administrators_keep_the_admin_shell(): void
    {
        $admin = $this->activeUser(Role::Administrator);

        $this->notificationFor($admin, [
            'judul' => 'Laporan makanan baru',
            'pesan' => 'Ada laporan makanan yang perlu ditinjau.',
            'tipe' => 'laporan',
        ]);

        $this->actingAs($admin)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('id="admin-sidebar"', false)
            ->assertDontSee('id="app-sidebar"', false)
            ->assertSee('Laporan makanan baru')
            ->assertSee('Laporan');
    }

    private function activeUser(Role $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'status_akun' => 'aktif',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function notificationFor(User $user, array $attributes = []): InAppNotification
    {
        return InAppNotification::create(array_merge([
            'user_id' => $user->id,
            'judul' => 'Judul notifikasi',
            'pesan' => 'Pesan notifikasi',
            'tipe' => 'claim',
        ], $attributes));
    }

    /**
     * Extract the page body so assertions do not include the header dropdown.
     */
    private function mainContent(TestResponse $response): string
    {
        preg_match('/<main\b[^>]*>(.*)<\/main>/s', (string) $response->getContent(), $matches);

        return $matches[1] ?? '';
    }
}
