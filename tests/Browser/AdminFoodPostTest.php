<?php

namespace Tests\Browser;

use App\Enums\Role;
use App\Models\DonorProfile;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AdminFoodPostTest extends DuskTestCase
{
    private static int $seq = 0;

    private function uniqueEmail(string $prefix): string
    {
        self::$seq++;

        return sprintf('%s-%d-%d-%s@example.com', strtolower($prefix), time(), self::$seq, strtolower(Str::random(4)));
    }

    /**
     * Administrator login melalui GUI, membuat postingan makanan baru untuk donatur, dan melihatnya di daftar.
     */
    public function test_administrator_can_view_and_create_food_post(): void
    {
        Artisan::call('migrate:fresh');

        $admin = $this->createAdmin();
        [, $donorProfile] = $this->createDonor();
        $category = FoodCategory::create(['nama_kategori' => 'Nasi & Lauk']);

        $sampleImage = $this->createTempJpeg();

        try {
            $this->browse(function (Browser $browser) use ($admin, $donorProfile, $category, $sampleImage) {
                $browser->logout()
                    ->visit('/login')
                    ->type('@login-email', $admin->email)
                    ->type('@login-password', 'Password123!')
                    ->press('@login-submit')
                    ->waitForLocation('/dashboard/admin')
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
                    ->assertSee('Nasi Kotak Donasi Admin')
                    ->logout();
            });
        } finally {
            @unlink($sampleImage);
        }
    }

    /**
     * Administrator login melalui GUI dengan akun dari test pertama, lalu mengubah dan menghapus postingan makanan dari test pertama.
     */
    public function test_administrator_can_edit_and_delete_food_post(): void
    {
        $admin = User::where('role', Role::Administrator)->firstOrFail();
        $foodPost = FoodPost::where('judul', 'Nasi Kotak Donasi Admin')->firstOrFail();

        $this->browse(function (Browser $browser) use ($admin, $foodPost) {
            $browser->logout()
                ->visit('/login')
                ->type('@login-email', $admin->email)
                ->type('@login-password', 'Password123!')
                ->press('@login-submit')
                ->waitForLocation('/dashboard/admin')
                ->visit('/admin/food-posts')
                ->assertSee('Nasi Kotak Donasi Admin')
                ->clickLink('Ubah')
                ->waitForLocation("/admin/food-posts/{$foodPost->id}/edit")
                ->assertSee('Ubah Postingan Makanan')
                ->clear('judul')
                ->type('judul', 'Nasi Kotak Donasi Admin Diperbarui')
                ->clear('sisa_porsi')
                ->type('sisa_porsi', '5')
                ->press('Simpan Perubahan')
                ->waitForLocation('/admin/food-posts')
                ->waitForText('Nasi Kotak Donasi Admin Diperbarui')
                ->assertSee('Postingan makanan berhasil diperbarui oleh Administrator')
                ->press('Hapus')
                ->acceptDialog()
                ->waitForText('Postingan makanan berhasil dihapus')
                ->assertDontSee('Nasi Kotak Donasi Admin Diperbarui')
                ->logout();
        });
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'email' => $this->uniqueEmail('admin'),
            'password' => Hash::make('Password123!'),
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
            'email' => $this->uniqueEmail('donor'),
            'password' => Hash::make('Password123!'),
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

