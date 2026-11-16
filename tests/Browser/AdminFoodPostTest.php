<?php

namespace Tests\Browser;

use App\Enums\FoodPostStatus;
use App\Enums\Role;
use App\Models\DonorProfile;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Http\UploadedFile;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminFoodPostTest extends DuskTestCase
{
    use DatabaseTruncation;

    /**
     * Administrator dapat melihat daftar postingan makanan dan membuat postingan baru untuk donatur.
     */
    public function test_administrator_can_view_and_create_food_post(): void
    {
        $admin = $this->createAdmin();
        [, $donorProfile] = $this->createDonor();
        $category = FoodCategory::create(['nama_kategori' => 'Nasi & Lauk']);

        $sampleImage = $this->createTempJpeg();

        try {
            $this->browse(function (Browser $browser) use ($admin, $donorProfile, $category, $sampleImage) {
                $browser->loginAs($admin)
                    ->visit('/admin/food-posts')
                    ->assertSee('Manajemen Postingan Makanan')
                    ->clickLink('Buat Postingan Makanan')
                    ->waitForLocation('/admin/food-posts/create')
                    ->select('donor_id', (string) $donorProfile->id)
                    ->type('judul', 'Nasi Kotak Donasi Admin')
                    ->select('category_id', (string) $category->id)
                    ->type('deskripsi', 'Postingan dibuat oleh Administrator.')
                    ->clear('jumlah_porsi')
                    ->type('jumlah_porsi', '20')
                    ->clear('sisa_porsi')
                    ->type('sisa_porsi', '20')
                    ->attach('foto', $sampleImage)
                    ->type('alamat', 'Jl. Donatur Pusat No. 10')
                    ->press('Simpan Postingan')
                    ->waitForLocation('/admin/food-posts')
                    ->assertSee('Postingan makanan berhasil dibuat oleh Administrator')
                    ->assertSee('Nasi Kotak Donasi Admin');
            });
        } finally {
            @unlink($sampleImage);
        }
    }

    /**
     * Administrator dapat mengubah dan menghapus postingan makanan milik donatur.
     */
    public function test_administrator_can_edit_and_delete_food_post(): void
    {
        $admin = $this->createAdmin();
        [, $donorProfile] = $this->createDonor();
        $category = FoodCategory::create(['nama_kategori' => 'Roti & Kue']);

        $foodPost = FoodPost::create([
            'judul' => 'Roti Manis Awal',
            'deskripsi' => 'Roti siap bagikan',
            'foto' => 'food-posts/sample.jpg',
            'jumlah_porsi' => 15,
            'sisa_porsi' => 15,
            'alamat' => 'Jl. Roti No. 3',
            'lat' => -6.2,
            'lng' => 106.8,
            'batas_konsumsi' => now()->addHours(6),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donorProfile->id,
            'category_id' => $category->id,
        ]);

        $this->browse(function (Browser $browser) use ($admin, $foodPost) {
            $browser->loginAs($admin)
                ->visit("/admin/food-posts/{$foodPost->id}/edit")
                ->assertSee('Ubah Postingan Makanan')
                ->clear('judul')
                ->type('judul', 'Roti Manis Diperbarui Admin')
                ->clear('sisa_porsi')
                ->type('sisa_porsi', '5')
                ->press('Simpan Perubahan')
                ->waitForLocation('/admin/food-posts')
                ->waitForText('Roti Manis Diperbarui Admin')
                ->assertSee('Postingan makanan berhasil diperbarui oleh Administrator')
                ->press('Hapus')
                ->acceptDialog()
                ->waitForText('Postingan makanan berhasil dihapus')
                ->assertDontSee('Roti Manis Diperbarui Admin');
        });
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
            'nama_usaha' => 'Dapur Donatur '.fake()->unique()->numerify('###'),
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor No. 1, Jakarta',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        return [$user, $profile];
    }

    /**
     * Generate a tiny valid 1x1 JPEG file without requiring the PHP GD extension.
     */
    private function createTempJpeg(): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'dusk-food-'.uniqid().'.jpg';

        // Minimal valid 1x1 pixel JPEG binary (no GD extension needed)
        $minimalJpegBase64 = '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';
        file_put_contents($path, base64_decode($minimalJpegBase64));

        return $path;
    }
}
