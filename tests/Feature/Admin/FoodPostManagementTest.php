<?php

namespace Tests\Feature\Admin;

use App\Enums\FoodPostStatus;
use App\Enums\Role;
use App\Models\Claim;
use App\Models\DonorProfile;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class FoodPostManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_view_all_food_posts(): void
    {
        $admin = $this->adminUser();
        [$donor1] = $this->donorProfile();
        [$donor2] = $this->donorProfile();
        $category = $this->foodCategory();

        $post1 = $this->foodPost($donor1, $category, ['judul' => 'Nasi Box Donor Satu']);
        $post2 = $this->foodPost($donor2, $category, ['judul' => 'Roti Bakar Donor Dua']);

        $this->actingAs($admin)
            ->get(route('admin.food-posts.index'))
            ->assertOk()
            ->assertSee($post1->judul)
            ->assertSee($post2->judul);
    }

    public function test_administrator_can_create_a_food_post_for_a_donor(): void
    {
        Storage::fake('public');

        $admin = $this->adminUser();
        [$donor] = $this->donorProfile();
        $category = $this->foodCategory();

        $response = $this->actingAs($admin)->post(route('admin.food-posts.store'), [
            'donor_id' => $donor->id,
            'judul' => 'Paket Sembako & Makanan Siap Saji',
            'deskripsi' => 'Dibuat oleh administrator untuk donatur.',
            'foto' => UploadedFile::fake()->create('paket.jpg', 100, 'image/jpeg'),
            'jumlah_porsi' => 25,
            'sisa_porsi' => 25,
            'alamat' => 'Jl. Admin Raya No. 10',
            'catatan_kurir' => 'Ambil di pos satpam depan.',
            'lat' => -6.2,
            'lng' => 106.8,
            'batas_konsumsi' => now()->addHours(8)->format('Y-m-d H:i:s'),
            'status' => FoodPostStatus::Tersedia->value,
            'category_id' => $category->id,
        ]);

        $response
            ->assertRedirect(route('admin.food-posts.index'))
            ->assertSessionHasNoErrors();

        $foodPost = FoodPost::query()->sole();
        $this->assertSame('Paket Sembako & Makanan Siap Saji', $foodPost->judul);
        $this->assertSame($donor->id, $foodPost->donor_id);
        $this->assertSame(25, $foodPost->jumlah_porsi);
        $this->assertSame(25, $foodPost->sisa_porsi);
        $this->assertSame(FoodPostStatus::Tersedia, $foodPost->status);
        Storage::disk('public')->assertExists($foodPost->foto);
    }

    public function test_administrator_can_edit_and_update_any_donors_food_post(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('food-posts/existing.jpg', 'old');

        $admin = $this->adminUser();
        [$donor] = $this->donorProfile();
        $category = $this->foodCategory();
        $foodPost = $this->foodPost($donor, $category, [
            'judul' => 'Judul Sebelum Diubah Admin',
            'foto' => 'food-posts/existing.jpg',
            'jumlah_porsi' => 20,
            'sisa_porsi' => 20,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.food-posts.edit', $foodPost))
            ->assertOk()
            ->assertSee('Judul Sebelum Diubah Admin');

        $response = $this->actingAs($admin)->put(route('admin.food-posts.update', $foodPost), [
            'donor_id' => $donor->id,
            'judul' => 'Judul Setelah Diubah Admin',
            'deskripsi' => 'Deskripsi diperbarui admin',
            'jumlah_porsi' => 20,
            'sisa_porsi' => 10,
            'alamat' => 'Jl. Baru No. 5',
            'catatan_kurir' => 'Catatan baru',
            'lat' => -6.21,
            'lng' => 106.81,
            'batas_konsumsi' => now()->addHours(5)->format('Y-m-d H:i:s'),
            'status' => FoodPostStatus::Habis->value,
            'category_id' => $category->id,
        ]);

        $response
            ->assertRedirect(route('admin.food-posts.index'))
            ->assertSessionHasNoErrors();

        $foodPost->refresh();
        $this->assertSame('Judul Setelah Diubah Admin', $foodPost->judul);
        $this->assertSame(10, $foodPost->sisa_porsi);
        $this->assertSame(FoodPostStatus::Habis, $foodPost->status);
    }

    public function test_administrator_can_delete_a_food_post_without_claims(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('food-posts/to-delete.jpg', 'photo');

        $admin = $this->adminUser();
        [$donor, $donorUser] = $this->donorProfile();
        $category = $this->foodCategory();
        $foodPost = $this->foodPost($donor, $category, ['foto' => 'food-posts/to-delete.jpg']);

        $response = $this->actingAs($admin)->delete(route('admin.food-posts.destroy', $foodPost));

        $response
            ->assertRedirect(route('admin.food-posts.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('food_posts', ['id' => $foodPost->id]);
        Storage::disk('public')->assertMissing('food-posts/to-delete.jpg');
        $this->assertDatabaseHas('notifications', [
            'user_id' => $donorUser->id,
            'tipe' => 'post',
        ]);
    }

    public function test_administrator_cannot_delete_a_food_post_that_has_claims(): void
    {
        $admin = $this->adminUser();
        [$donor] = $this->donorProfile();
        $category = $this->foodCategory();
        $foodPost = $this->foodPost($donor, $category);

        $recipient = User::factory()->create([
            'role' => Role::Penerima,
            'status_akun' => 'aktif',
        ]);
        $recipientProfile = $recipient->recipientProfile()->create([
            'nama_lembaga' => 'Panti Asuhan Uji',
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => 'Jl. Panti',
        ]);

        Claim::create([
            'jumlah_porsi' => 5,
            'status' => 'pending',
            'tanggal_klaim' => today(),
            'post_id' => $foodPost->id,
            'recipient_id' => $recipientProfile->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.food-posts.destroy', $foodPost))
            ->assertRedirect(route('admin.food-posts.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('food_posts', ['id' => $foodPost->id]);
    }

    public function test_non_administrators_cannot_access_admin_food_posts(): void
    {
        [, $donorUser] = $this->donorProfile();

        $this->actingAs($donorUser)
            ->get(route('admin.food-posts.index'))
            ->assertForbidden();
    }

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => Role::Administrator,
            'status_akun' => 'aktif',
        ]);
    }

    /**
     * @return array{0: DonorProfile, 1: User}
     */
    private function donorProfile(): array
    {
        $user = User::factory()->create([
            'role' => Role::Donor,
            'status_akun' => 'aktif',
        ]);

        $profile = $user->donorProfile()->create([
            'nama_usaha' => fake()->unique()->company(),
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        return [$profile, $user];
    }

    private function foodCategory(): FoodCategory
    {
        return FoodCategory::create([
            'nama_kategori' => fake()->unique()->word(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function foodPost(DonorProfile $donor, FoodCategory $category, array $overrides = []): FoodPost
    {
        return FoodPost::create(array_merge([
            'judul' => 'Nasi Box',
            'deskripsi' => 'Nasi box siap santap',
            'foto' => 'food-posts/test.jpg',
            'jumlah_porsi' => 10,
            'sisa_porsi' => 10,
            'alamat' => 'Jl. Merdeka 1',
            'lat' => -6.2,
            'lng' => 106.8,
            'batas_konsumsi' => now()->addDay(),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donor->id,
            'category_id' => $category->id,
        ], $overrides));
    }
}
