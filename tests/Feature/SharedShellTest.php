<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Claim;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class SharedShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_donor_shell_has_one_document_one_sidebar_and_one_content_surface(): void
    {
        $user = $this->activeUser(Role::Donor);
        $user->donorProfile()->create([
            'nama_usaha' => 'Dapur Berkah',
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.donor'));
        $response->assertOk();

        $html = $response->getContent();

        $this->assertSame(1, substr_count(strtolower($html), '<html'));
        $this->assertSame(1, substr_count($html, 'id="app-sidebar"'));
        $this->assertSame(1, substr_count($html, 'data-content-surface'));
        $this->assertSame(1, substr_count($html, 'dusk="notification-bell"'));
        $this->assertStringContainsString('Buka menu', $html);
        $this->assertStringContainsString('aria-label="Breadcrumb"', $html);
        $this->assertStringContainsString('FoodBridge', $html);
        $this->assertStringContainsString('Donor Hub', $html);
        $this->assertStringContainsString('x-ref="navSearch"', $html);
        $this->assertStringNotContainsString('id="admin-sidebar"', $html);
        $this->assertStringNotContainsString('href="#"', $html);
    }

    public function test_donor_active_navigation_carries_aria_current(): void
    {
        $user = $this->activeUser(Role::Donor);
        $user->donorProfile()->create([
            'nama_usaha' => 'Dapur Berkah',
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        $html = $this->actingAs($user)->get(route('dashboard.donor'))->assertOk()->getContent();
        $sidebar = $this->sidebar($html, 'app-sidebar');

        $this->assertStringContainsString('aria-current="page"', $sidebar);
        $this->assertStringContainsString('Posting Makanan', $sidebar);
        $this->assertStringNotContainsString('Ulasan dan Laporan', $sidebar);
        $this->assertStringNotContainsString('Katalog Makanan', $sidebar);
    }

    public function test_donor_pending_claim_badge_renders(): void
    {
        $donor = $this->activeUser(Role::Donor);
        $profile = $donor->donorProfile()->create([
            'nama_usaha' => 'Dapur Berkah',
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);
        $recipient = $this->activeUser(Role::Penerima);
        $recipientProfile = $recipient->recipientProfile()->create([
            'nama_lembaga' => 'Panti Harapan',
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => 'Jl. Penerima',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);
        $category = FoodCategory::create(['nama_kategori' => 'Karbohidrat']);
        $post = FoodPost::create([
            'judul' => 'Nasi Box',
            'deskripsi' => 'Nasi box siap saji',
            'foto' => 'food.webp',
            'jumlah_porsi' => 10,
            'sisa_porsi' => 10,
            'alamat' => 'Jl. Donor',
            'lat' => -6.2,
            'lng' => 106.8,
            'batas_konsumsi' => now()->addDay(),
            'status' => 'tersedia',
            'donor_id' => $profile->id,
            'category_id' => $category->id,
        ]);

        Claim::create([
            'jumlah_porsi' => 1,
            'status' => 'pending',
            'tanggal_klaim' => now()->toDateString(),
            'post_id' => $post->id,
            'recipient_id' => $recipientProfile->id,
        ]);

        $html = $this->actingAs($donor)->get(route('dashboard.donor'))->assertOk()->getContent();
        $sidebar = $this->sidebar($html, 'app-sidebar');

        $this->assertMatchesRegularExpression('/>\s*1\s*</', $sidebar);
    }

    public function test_recipient_shell_has_one_document_one_sidebar_and_one_content_surface(): void
    {
        $user = $this->activeUser(Role::Penerima);
        $user->recipientProfile()->create([
            'nama_lembaga' => 'Panti Harapan Mulia',
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => 'Jl. Penerima',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard.recipient'));
        $response->assertOk();

        $html = $response->getContent();

        $this->assertSame(1, substr_count(strtolower($html), '<html'));
        $this->assertSame(1, substr_count($html, 'id="app-sidebar"'));
        $this->assertSame(1, substr_count($html, 'data-content-surface'));
        $this->assertSame(1, substr_count($html, 'dusk="notification-bell"'));
        $this->assertStringContainsString('Buka menu', $html);
        $this->assertStringContainsString('aria-label="Breadcrumb"', $html);
        $this->assertStringContainsString('Penerima / Yayasan', $html);
        $this->assertStringContainsString('Katalog Makanan', $html);
        $this->assertStringContainsString('Profil Saya', $html);
        $this->assertStringNotContainsString('Posting Makanan', $html);
        $this->assertStringNotContainsString('id="admin-sidebar"', $html);
        $this->assertStringNotContainsString('href="#"', $html);
    }

    public function test_recipient_active_navigation_carries_aria_current(): void
    {
        $user = $this->activeUser(Role::Penerima);
        $user->recipientProfile()->create([
            'nama_lembaga' => 'Panti Harapan Mulia',
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => 'Jl. Penerima',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        $html = $this->actingAs($user)->get(route('dashboard.recipient'))->assertOk()->getContent();
        $sidebar = $this->sidebar($html, 'app-sidebar');

        $this->assertStringContainsString('aria-current="page"', $sidebar);
        $this->assertStringNotContainsString('Ulasan dan Laporan', $sidebar);
    }

    public function test_admin_shell_has_one_document_one_sidebar_and_one_content_surface(): void
    {
        $admin = $this->activeUser(Role::Administrator);

        $response = $this->actingAs($admin)->get(route('dashboard.admin'));
        $response->assertOk();

        $html = $response->getContent();

        $this->assertSame(1, substr_count(strtolower($html), '<html'));
        $this->assertSame(1, substr_count($html, 'id="admin-sidebar"'));
        $this->assertSame(0, substr_count($html, 'id="app-sidebar"'));
        $this->assertSame(1, substr_count($html, 'data-content-surface'));
        $this->assertSame(1, substr_count($html, 'dusk="notification-bell"'));
        $this->assertStringContainsString('Buka menu', $html);
        $this->assertStringContainsString('aria-label="Breadcrumb"', $html);
        $this->assertStringContainsString('FoodBridge', $html);
        $this->assertStringContainsString('Menunggu Verifikasi:', $html);
        $this->assertStringContainsString('MENU UTAMA', $html);
        $this->assertStringContainsString('MANAJEMEN DATA', $html);
        $this->assertStringContainsString('Verifikasi Akun', $html);
        $this->assertStringContainsString('Postingan Makanan', $html);
        $this->assertStringNotContainsString('Laporan Makanan', $html);
        $this->assertStringContainsString('Pengguna', $html);
        $this->assertStringContainsString('Klaim', $html);
        $this->assertStringContainsString('Kategori Gizi', $html);
        $this->assertStringNotContainsString('href="#"', $html);
    }

    public function test_admin_header_shows_page_title(): void
    {
        $admin = $this->activeUser(Role::Administrator);

        $html = $this->actingAs($admin)->get(route('dashboard.admin'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<h1[^>]*>\s*Dashboard\s*<\/h1>/', $html);
    }

    public function test_admin_active_navigation_carries_aria_current(): void
    {
        $admin = $this->activeUser(Role::Administrator);

        $html = $this->actingAs($admin)->get(route('dashboard.admin'))->assertOk()->getContent();
        $sidebar = $this->sidebar($html, 'admin-sidebar');

        $this->assertStringContainsString('aria-current="page"', $sidebar);
    }

    public function test_guest_pages_do_not_render_the_shared_shell(): void
    {
        $response = $this->get(route('login'))->assertOk();

        $html = $response->getContent();

        $this->assertStringNotContainsString('id="app-sidebar"', $html);
        $this->assertStringNotContainsString('id="admin-sidebar"', $html);
        $this->assertStringNotContainsString('Buka menu', $html);
        $this->assertStringNotContainsString('data-content-surface', $html);
    }

    private function activeUser(Role $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'status_akun' => 'aktif',
        ]);
    }

    private function sidebar(string $html, string $id): string
    {
        preg_match('/<aside[^>]*id="'.preg_quote($id, '/').'".*?<\/aside>/s', $html, $matches);

        return $matches[0] ?? '';
    }
}
