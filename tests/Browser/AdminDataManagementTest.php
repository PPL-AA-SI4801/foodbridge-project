<?php

namespace Tests\Browser;

use App\Enums\FoodPostStatus;
use App\Enums\Role;
use App\Models\Claim;
use App\Models\DonorProfile;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\RecipientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminDataManagementTest extends DuskTestCase
{
    use DatabaseTruncation;

    /**
     * Administrator dapat membuat akun donor lengkap dengan profil usahanya.
     */
    public function test_administrator_can_create_a_user(): void
    {
        $admin = $this->createAdmin();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/admin/users')
                ->assertSee('Manajemen Pengguna')
                ->clickLink('Buat Akun')
                ->waitForLocation('/admin/users/create')
                ->type('nama', 'Donor Baru Dusk')
                ->type('email', 'donor-baru-dusk@example.test')
                ->type('no_hp', '081234567890')
                ->select('role', 'donor')
                ->waitFor('#nama_usaha')
                ->type('password', 'rahasia123')
                ->type('password_confirmation', 'rahasia123')
                ->type('nama_usaha', 'Dapur Baru Dusk')
                ->type('jenis_usaha', 'Restoran')
                ->type('alamat', 'Jl. Baru Dusk No. 1')
                ->clear('lat')
                ->type('lat', '-6.2')
                ->clear('lng')
                ->type('lng', '106.8')
                ->press('Simpan Akun')
                ->waitForLocation('/admin/users')
                ->assertSee('berhasil dibuat')
                ->assertSee('Donor Baru Dusk')
                ->assertSee('Dapur Baru Dusk');
        });

        $this->assertDatabaseHas('users', [
            'email' => 'donor-baru-dusk@example.test',
            'role' => Role::Donor->value,
        ]);
        $this->assertDatabaseHas('donor_profiles', ['nama_usaha' => 'Dapur Baru Dusk']);
    }

    /**
     * Administrator dapat membuat kategori gizi baru.
     */
    public function test_administrator_can_create_a_category(): void
    {
        $admin = $this->createAdmin();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/admin/categories')
                ->assertSee('Kategori Gizi')
                ->clickLink('Buat Kategori')
                ->waitForLocation('/admin/categories/create')
                ->type('nama_kategori', 'Makanan Berkuah')
                ->press('Simpan Kategori')
                ->waitForLocation('/admin/categories')
                ->assertSee('berhasil dibuat')
                ->assertSee('Makanan Berkuah');
        });

        $this->assertDatabaseHas('food_categories', ['nama_kategori' => 'Makanan Berkuah']);
    }

    /**
     * Administrator dapat membuat klaim untuk penerima lalu menyetujuinya.
     */
    public function test_administrator_can_create_and_approve_a_claim(): void
    {
        $admin = $this->createAdmin();
        [, $donorProfile] = $this->createDonor();
        $recipient = $this->createRecipient();
        $category = FoodCategory::create(['nama_kategori' => 'Karbohidrat']);
        $post = $this->createFoodPost($donorProfile, $category);

        $this->browse(function (Browser $browser) use ($admin, $recipient, $post) {
            $browser->loginAs($admin)
                ->visit('/admin/claims')
                ->assertSee('Manajemen Klaim')
                ->clickLink('Buat Klaim')
                ->waitForLocation('/admin/claims/create')
                ->select('recipient_id', (string) $recipient->id)
                ->select('post_id', (string) $post->id)
                ->clear('jumlah_porsi')
                ->type('jumlah_porsi', '3')
                ->press('Simpan Klaim')
                ->waitForLocation('/admin/claims')
                ->assertSee('Klaim berhasil dibuat')
                ->assertSee($post->judul);
        });

        $claim = Claim::query()->sole();

        $this->assertSame('pending', $claim->status);

        $this->browse(function (Browser $browser) use ($admin, $claim) {
            $browser->loginAs($admin)
                ->visit("/admin/claims/{$claim->id}/edit")
                ->assertSee('Ubah Klaim')
                ->select('status', 'approved')
                ->press('Simpan Perubahan')
                ->waitForLocation('/admin/claims')
                ->assertSee('Klaim berhasil diperbarui')
                ->assertSee('Disetujui');
        });

        $this->assertSame('approved', $claim->fresh()->status);
        $this->assertSame(7, $post->fresh()->sisa_porsi);
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => Role::Administrator,
            'status_akun' => 'aktif',
        ]);
    }

    /**
     * @return array{0: User, 1: DonorProfile}
     */
    private function createDonor(): array
    {
        $user = User::factory()->create([
            'role' => Role::Donor,
            'status_akun' => 'aktif',
        ]);

        $profile = $user->donorProfile()->create([
            'nama_usaha' => 'Dapur Dusk '.fake()->unique()->numerify('###'),
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor Dusk No. 1, Jakarta',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        return [$user, $profile];
    }

    private function createRecipient(): RecipientProfile
    {
        $user = User::factory()->create([
            'role' => Role::Penerima,
            'status_akun' => 'aktif',
        ]);

        return $user->recipientProfile()->create([
            'nama_lembaga' => 'Panti Dusk '.fake()->unique()->numerify('###'),
            'jenis_lembaga' => 'Panti asuhan',
            'alamat' => 'Jl. Penerima Dusk No. 2, Jakarta',
            'lat' => -6.3,
            'lng' => 106.9,
        ]);
    }

    private function createFoodPost(DonorProfile $donor, FoodCategory $category): FoodPost
    {
        return FoodPost::create([
            'judul' => 'Nasi Kotak Dusk',
            'deskripsi' => 'Nasi kotak untuk pengujian admin.',
            'foto' => 'seed/placeholder.webp',
            'jumlah_porsi' => 10,
            'sisa_porsi' => 10,
            'alamat' => 'Jl. Makanan Dusk No. 3, Jakarta',
            'lat' => -6.2,
            'lng' => 106.8,
            'batas_konsumsi' => now()->addDay(),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donor->id,
            'category_id' => $category->id,
        ]);
    }
}
