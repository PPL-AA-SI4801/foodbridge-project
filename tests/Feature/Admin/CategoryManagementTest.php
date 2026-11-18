<?php

namespace Tests\Feature\Admin;

use App\Enums\AccountStatus;
use App\Enums\FoodPostStatus;
use App\Enums\Role;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_donor_and_recipient_cannot_manage_categories(): void
    {
        $category = FoodCategory::create(['nama_kategori' => 'Karbohidrat']);

        foreach ([$this->activeUser(Role::Donor), $this->activeUser(Role::Penerima)] as $actor) {
            $this->actingAs($actor)->get(route('admin.categories.index'))->assertForbidden();
            $this->actingAs($actor)->get(route('admin.categories.create'))->assertForbidden();
            $this->actingAs($actor)->post(route('admin.categories.store'), [
                'nama_kategori' => 'Protein',
            ])->assertForbidden();
            $this->actingAs($actor)->get(route('admin.categories.edit', $category))->assertForbidden();
            $this->actingAs($actor)->put(route('admin.categories.update', $category), [
                'nama_kategori' => 'Protein',
            ])->assertForbidden();
            $this->actingAs($actor)->delete(route('admin.categories.destroy', $category))->assertForbidden();
        }

        $this->assertDatabaseHas('food_categories', ['id' => $category->id]);
    }

    public function test_administrator_can_view_categories_with_post_counts(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $used = FoodCategory::create(['nama_kategori' => 'Karbohidrat']);
        FoodCategory::create(['nama_kategori' => 'Kategori Kosong']);
        $this->foodPost($used);

        $this->actingAs($admin)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('Kategori Gizi')
            ->assertSee('Karbohidrat')
            ->assertSee('1 postingan')
            ->assertSee('Kategori Kosong')
            ->assertSee('0 postingan');
    }

    public function test_administrator_can_create_a_category(): void
    {
        $admin = $this->activeUser(Role::Administrator);

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), ['nama_kategori' => 'Protein'])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('food_categories', ['nama_kategori' => 'Protein']);
    }

    public function test_category_name_must_be_unique(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        FoodCategory::create(['nama_kategori' => 'Protein']);

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), ['nama_kategori' => 'Protein'])
            ->assertSessionHasErrors('nama_kategori');

        $this->assertDatabaseCount('food_categories', 1);
    }

    public function test_administrator_can_update_a_category_and_ignore_its_own_name(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $category = FoodCategory::create(['nama_kategori' => 'Protein']);

        $this->actingAs($admin)
            ->put(route('admin.categories.update', $category), ['nama_kategori' => 'Protein'])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertSame('Protein', $category->fresh()->nama_kategori);

        $this->actingAs($admin)
            ->put(route('admin.categories.update', $category), ['nama_kategori' => 'Protein Nabati'])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertSame('Protein Nabati', $category->fresh()->nama_kategori);
    }

    public function test_update_rejects_another_category_name(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        FoodCategory::create(['nama_kategori' => 'Protein']);
        $category = FoodCategory::create(['nama_kategori' => 'Sayuran']);

        $this->actingAs($admin)
            ->put(route('admin.categories.update', $category), ['nama_kategori' => 'Protein'])
            ->assertSessionHasErrors('nama_kategori');

        $this->assertSame('Sayuran', $category->fresh()->nama_kategori);
    }

    public function test_administrator_can_delete_an_unused_category(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $category = FoodCategory::create(['nama_kategori' => 'Kategori Kosong']);

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('status', 'Kategori berhasil dihapus.');

        $this->assertDatabaseMissing('food_categories', ['id' => $category->id]);
    }

    public function test_category_in_use_cannot_be_deleted(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $category = FoodCategory::create(['nama_kategori' => 'Karbohidrat']);
        $this->foodPost($category);

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('error', 'Kategori tidak dapat dihapus karena masih digunakan oleh postingan makanan.');

        $this->assertDatabaseHas('food_categories', ['id' => $category->id]);
    }

    private function activeUser(Role $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'status_akun' => AccountStatus::Aktif,
        ]);
    }

    private function foodPost(FoodCategory $category): FoodPost
    {
        $donor = $this->activeUser(Role::Donor);
        $profile = $donor->donorProfile()->create([
            'nama_usaha' => 'Dapur '.fake()->unique()->numerify('###'),
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor No. 1',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        return FoodPost::create([
            'judul' => 'Makanan '.fake()->unique()->numerify('###'),
            'deskripsi' => 'Deskripsi makanan uji',
            'foto' => 'seed/placeholder.webp',
            'jumlah_porsi' => 10,
            'sisa_porsi' => 10,
            'alamat' => 'Jl. Makanan No. 1',
            'lat' => -6.2,
            'lng' => 106.8,
            'batas_konsumsi' => now()->addDay(),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $profile->id,
            'category_id' => $category->id,
        ]);
    }
}
