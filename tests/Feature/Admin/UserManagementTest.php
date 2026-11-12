<?php

namespace Tests\Feature\Admin;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_view_donor_and_recipient_accounts(): void
    {
        $admin = $this->activeUser(Role::Administrator, 'Administrator Satu');
        $donor = $this->activeUser(Role::Donor, 'Donor Terdaftar', 'donor@example.test');
        $donor->donorProfile()->create([
            'nama_usaha' => 'Dapur Donor',
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor No. 1',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        $recipient = $this->activeUser(Role::Penerima, 'Penerima Terdaftar');
        $recipient->recipientProfile()->create([
            'nama_lembaga' => 'Panti Penerima',
            'jenis_lembaga' => 'Panti asuhan',
            'alamat' => 'Jl. Penerima No. 2',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk()
            ->assertSee('Manajemen Pengguna')
            ->assertSee('Donor Terdaftar')
            ->assertSee('donor@example.test')
            ->assertSee('Dapur Donor')
            ->assertSee('Penerima Terdaftar')
            ->assertSee('Panti Penerima')
            ->assertViewHas('users', fn ($users) => $users->total() === 2);
    }

    public function test_administrator_can_search_and_filter_accounts(): void
    {
        $admin = $this->activeUser(Role::Administrator);

        $this->activeUser(Role::Donor, 'Donor Khusus');
        $blocked = $this->activeUser(Role::Penerima, 'Penerima Diblokir');
        $blocked->update(['status_akun' => AccountStatus::Diblokir]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'q' => 'Khusus',
            'status' => AccountStatus::Aktif->value,
        ]));

        $response->assertOk()
            ->assertSee('Donor Khusus')
            ->assertDontSee('Penerima Diblokir');
    }

    public function test_administrator_can_block_an_active_donor_or_recipient(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $donor = $this->activeUser(Role::Donor);
        $recipient = $this->activeUser(Role::Penerima);

        $this->actingAs($admin)
            ->post(route('admin.users.block', $donor))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status', 'Akun berhasil diblokir.');

        $this->actingAs($admin)
            ->post(route('admin.users.block', $recipient))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status', 'Akun berhasil diblokir.');

        $this->assertSame(AccountStatus::Diblokir, $donor->fresh()->status_akun);
        $this->assertSame(AccountStatus::Diblokir, $recipient->fresh()->status_akun);
    }

    public function test_only_active_accounts_can_be_blocked(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $pending = $this->activeUser(Role::Donor);
        $pending->update(['status_akun' => AccountStatus::Menunggu]);

        $this->actingAs($admin)
            ->post(route('admin.users.block', $pending))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'Hanya akun aktif yang dapat diblokir.');

        $this->assertSame(AccountStatus::Menunggu, $pending->fresh()->status_akun);
    }

    public function test_already_blocked_accounts_cannot_be_blocked_again(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $blocked = $this->activeUser(Role::Penerima);
        $blocked->update(['status_akun' => AccountStatus::Diblokir]);

        $this->actingAs($admin)
            ->post(route('admin.users.block', $blocked))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'Akun ini sudah diblokir.');

        $this->assertSame(AccountStatus::Diblokir, $blocked->fresh()->status_akun);
    }

    public function test_administrator_accounts_cannot_be_blocked(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $otherAdmin = $this->activeUser(Role::Administrator);

        $this->actingAs($admin)
            ->post(route('admin.users.block', $otherAdmin))
            ->assertNotFound();

        $this->assertSame(AccountStatus::Aktif, $otherAdmin->fresh()->status_akun);
    }

    public function test_non_administrator_cannot_block_an_account(): void
    {
        $donor = $this->activeUser(Role::Donor);
        $otherDonor = $this->activeUser(Role::Donor);

        $this->actingAs($donor)
            ->post(route('admin.users.block', $otherDonor))
            ->assertForbidden();

        $this->assertSame(AccountStatus::Aktif, $otherDonor->fresh()->status_akun);
    }

    public function test_non_administrator_cannot_view_user_management(): void
    {
        $donor = $this->activeUser(Role::Donor);

        $this->actingAs($donor)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_user_management(): void
    {
        $this->get(route('admin.users.index'))
            ->assertRedirect(route('login'));
    }

    private function activeUser(Role $role, ?string $name = null, ?string $email = null): User
    {
        return User::factory()->create([
            'nama' => $name ?? fake()->name(),
            'email' => $email ?? $role->value.'-'.fake()->unique()->numerify('###').'@example.test',
            'role' => $role,
            'status_akun' => AccountStatus::Aktif,
        ]);
    }
}
