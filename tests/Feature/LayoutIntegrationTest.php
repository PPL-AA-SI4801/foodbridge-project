<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\DonorProfile;
use App\Models\RecipientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class LayoutIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_donor_pages_use_the_shared_shell_and_donor_menu(): void
    {
        [$user, $profile] = $this->donorProfile();

        $response = $this->actingAs($user)->get(route('dashboard.donor'));

        $response
            ->assertOk()
            ->assertSee('id="app-sidebar"', false)
            ->assertSee('Buka menu')
            ->assertSee('Donor Hub')
            ->assertSee('Posting Makanan')
            ->assertSee('Profil Saya')
            ->assertDontSee('Katalog Makanan')
            ->assertSee($profile->nama_usaha);

        $this->actingAs($user)
            ->get(route('donor.food-posts.create'))
            ->assertOk()
            ->assertSee('id="app-sidebar"', false)
            ->assertSee('Donor Hub')
            ->assertSee('Posting Makanan');

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('id="app-sidebar"', false)
            ->assertSee('Profil Saya')
            ->assertSee($profile->nama_usaha);
    }

    public function test_recipient_pages_use_the_shared_shell_and_recipient_menu(): void
    {
        [$user, $profile] = $this->recipientProfile();

        $response = $this->actingAs($user)->get(route('dashboard.recipient'));

        $response
            ->assertOk()
            ->assertSee('id="app-sidebar"', false)
            ->assertSee('Buka menu')
            ->assertSee('Penerima / Yayasan')
            ->assertSee('Katalog Makanan')
            ->assertSee('Profil Saya')
            ->assertDontSee('Posting Makanan')
            ->assertSee($profile->nama_lembaga);

        $this->actingAs($user)
            ->get(route('recipient.catalog.index'))
            ->assertOk()
            ->assertSee('id="app-sidebar"', false)
            ->assertSee('Katalog Peta Pangan')
            ->assertSee('Klaim hari ini')
            ->assertSee($profile->nama_lembaga);
    }

    public function test_guest_pages_do_not_render_the_authenticated_shell(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('id="app-sidebar"', false)
            ->assertDontSee('Buka menu');
    }

    public function test_admin_keeps_the_admin_shell(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Administrator,
            'status_akun' => 'aktif',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard.admin'))
            ->assertOk()
            ->assertSee('id="admin-sidebar"', false)
            ->assertDontSee('id="app-sidebar"', false);
    }

    /**
     * @return array{0: User, 1: DonorProfile}
     */
    private function donorProfile(): array
    {
        $user = User::factory()->create([
            'role' => Role::Donor,
            'status_akun' => 'aktif',
        ]);

        $profile = $user->donorProfile()->create([
            'nama_usaha' => 'Dapur Berkah Nusantara',
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        return [$user, $profile];
    }

    /**
     * @return array{0: User, 1: RecipientProfile}
     */
    private function recipientProfile(): array
    {
        $user = User::factory()->create([
            'role' => Role::Penerima,
            'status_akun' => 'aktif',
        ]);

        $profile = $user->recipientProfile()->create([
            'nama_lembaga' => 'Panti Harapan Mulia',
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => 'Jl. Penerima',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        return [$user, $profile];
    }
}
