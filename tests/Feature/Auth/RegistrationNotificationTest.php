<?php

namespace Tests\Feature\Auth;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\InAppNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class RegistrationNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_a_donor_notifies_every_active_administrator(): void
    {
        Storage::fake('local');

        $administrator = $this->activeAdministrator();
        $secondAdministrator = $this->activeAdministrator();
        $inactiveAdministrator = User::factory()->create([
            'role' => Role::Administrator,
            'status_akun' => AccountStatus::Diblokir->value,
        ]);

        $this->post(route('register'), [
            'nama' => 'Pemilik Resto Baru',
            'email' => 'resto-baru@example.com',
            'no_hp' => '081234567890',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => Role::Donor->value,
            'nama_usaha' => 'Resto Baru',
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Baru No. 1, Bandung',
            'jenis_dokumen' => 'nib',
            'dokumen' => UploadedFile::fake()->create('nib.pdf', 100, 'application/pdf'),
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Pendaftaran berhasil. Akun Anda sedang menunggu verifikasi administrator.');

        $applicant = User::query()
            ->where('email', 'resto-baru@example.com')
            ->firstOrFail();

        $notifications = InAppNotification::query()
            ->whereIn('user_id', [$administrator->id, $secondAdministrator->id])
            ->get();

        $this->assertCount(2, $notifications);

        foreach ($notifications as $notification) {
            $this->assertSame('akun', $notification->tipe);
            $this->assertSame('Akun Menunggu Verifikasi', $notification->judul);
            $this->assertStringContainsString('Resto Baru', $notification->pesan);
            $this->assertStringContainsString('Donor', $notification->pesan);
            $this->assertFalse($notification->is_read);
        }

        $this->actingAs($administrator)
            ->get(route('notifications.open', $notifications->first()))
            ->assertRedirect(route('admin.verifications.index'));

        $this->assertSame(
            0,
            InAppNotification::query()
                ->where('user_id', $inactiveAdministrator->id)
                ->count(),
        );
    }

    public function test_registering_a_recipient_uses_the_institution_name_in_the_admin_notification(): void
    {
        Storage::fake('local');

        $administrator = $this->activeAdministrator();

        $this->post(route('register'), [
            'nama' => 'Pengurus Panti Baru',
            'email' => 'panti-baru@example.com',
            'no_hp' => '081234567891',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => Role::Penerima->value,
            'nama_lembaga' => 'Panti Baru',
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => 'Jl. Baru No. 2, Bandung',
            'jenis_dokumen' => 'nib',
            'dokumen' => UploadedFile::fake()->create('nib.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('login'));

        $notification = InAppNotification::query()
            ->where('user_id', $administrator->id)
            ->sole();

        $this->assertStringContainsString('Panti Baru', $notification->pesan);
        $this->assertStringContainsString('Penerima', $notification->pesan);
    }

    private function activeAdministrator(): User
    {
        return User::factory()->create([
            'role' => Role::Administrator,
            'status_akun' => AccountStatus::Aktif->value,
        ]);
    }
}
