<?php

namespace Tests\Feature\Donor;

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
class FoodPostTest extends TestCase
{
    use RefreshDatabase;

    public function test_donor_only_sees_their_own_food_posts(): void
    {
        [$donor] = $this->donorProfile();
        [$otherDonor] = $this->donorProfile();
        $category = $this->foodCategory();

        $ownPost = $this->foodPost($donor, $category, ['judul' => 'Makanan Sendiri']);
        $otherPost = $this->foodPost($otherDonor, $category, ['judul' => 'Makanan Donor Lain']);

        $response = $this->actingAs($donor->user)->get(route('donor.food-posts.index'));

        $response
            ->assertOk()
            ->assertSee($ownPost->judul)
            ->assertDontSee($otherPost->judul);
    }

    public function test_create_page_shows_the_revised_donor_form(): void
    {
        [$donor] = $this->donorProfile();
        $this->foodCategory();

        $response = $this->actingAs($donor->user)->get(route('donor.food-posts.create'));

        $response
            ->assertOk()
            ->assertSee('Posting Surplus Makanan')
            ->assertSee('Protokol Keamanan Otomatis')
            ->assertSee('Detail Makanan Berlebih')
            ->assertSee('Batas Aman Konsumsi')
            ->assertSee('Titik & Instruksi Jemput', false)
            ->assertSee('Catatan Khusus untuk Kurir / Resepsionis')
            ->assertSee('name="catatan_kurir"', false)
            ->assertSee('Pernyataan Standar Higienitas')
            ->assertSee('Simpan sebagai Draf')
            ->assertSee('Draf belum tersedia pada versi ini.')
            ->assertSee('Terbitkan Postingan Sekarang');
    }

    public function test_edit_page_uses_the_shared_donor_form(): void
    {
        [$donor] = $this->donorProfile();
        $category = $this->foodCategory();
        $foodPost = $this->foodPost($donor, $category);

        $response = $this->actingAs($donor->user)->get(route('donor.food-posts.edit', $foodPost));

        $response
            ->assertOk()
            ->assertSee('Ubah Postingan Makanan')
            ->assertSee('Simpan Perubahan')
            ->assertSee('Foto saat ini tersimpan')
            ->assertDontSee('Simpan sebagai Draf');
    }

    public function test_donor_can_create_a_food_post(): void
    {
        Storage::fake('public');

        [$donor] = $this->donorProfile();
        $category = $this->foodCategory();

        $response = $this->actingAs($donor->user)->post(route('donor.food-posts.store'), $this->validPayload($category, [
            'judul' => 'Nasi Box Ayam',
            'jumlah_porsi' => 12,
            'catatan_kurir' => 'Hubungi resepsionis sebelum tiba.',
            'foto' => UploadedFile::fake()->create('makanan.jpg', 100, 'image/jpeg'),
        ]));

        $response
            ->assertRedirect(route('donor.food-posts.index'))
            ->assertSessionHasNoErrors();

        $foodPost = FoodPost::query()->sole();

        $this->assertSame('Nasi Box Ayam', $foodPost->judul);
        $this->assertSame($donor->id, $foodPost->donor_id);
        $this->assertSame($category->id, $foodPost->category_id);
        $this->assertSame(12, $foodPost->jumlah_porsi);
        $this->assertSame(12, $foodPost->sisa_porsi);
        $this->assertSame('Hubungi resepsionis sebelum tiba.', $foodPost->catatan_kurir);
        $this->assertSame(FoodPostStatus::Tersedia, $foodPost->status);
        Storage::disk('public')->assertExists($foodPost->foto);
    }

    public function test_courier_note_is_optional_and_limited_to_500_characters(): void
    {
        Storage::fake('public');

        [$donor] = $this->donorProfile();
        $category = $this->foodCategory();

        $this->actingAs($donor->user)->post(route('donor.food-posts.store'), $this->validPayload($category, [
            'foto' => UploadedFile::fake()->create('tanpa-catatan.jpg', 100, 'image/jpeg'),
        ]))->assertSessionHasNoErrors();

        $this->assertNull(FoodPost::query()->sole()->catatan_kurir);

        $response = $this->actingAs($donor->user)->post(route('donor.food-posts.store'), $this->validPayload($category, [
            'catatan_kurir' => str_repeat('a', 501),
            'foto' => UploadedFile::fake()->create('catatan-terlalu-panjang.jpg', 100, 'image/jpeg'),
        ]));

        $response->assertSessionHasErrors('catatan_kurir');
        $this->assertDatabaseCount('food_posts', 1);
    }

    public function test_food_post_creation_requires_a_photo_and_a_future_consumption_deadline(): void
    {
        Storage::fake('public');

        [$donor] = $this->donorProfile();
        $category = $this->foodCategory();

        $response = $this->actingAs($donor->user)->post(route('donor.food-posts.store'), $this->validPayload($category, [
            'batas_konsumsi' => now()->subDay()->format('Y-m-d H:i:s'),
        ]));

        $response->assertSessionHasErrors(['foto', 'batas_konsumsi']);
        $this->assertDatabaseCount('food_posts', 0);
    }

    public function test_donor_can_update_a_food_post_without_replacing_its_photo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('food-posts/old.jpg', 'old');

        [$donor] = $this->donorProfile();
        $category = $this->foodCategory();
        $foodPost = $this->foodPost($donor, $category, [
            'judul' => 'Judul Lama',
            'foto' => 'food-posts/old.jpg',
            'jumlah_porsi' => 10,
            'sisa_porsi' => 10,
            'catatan_kurir' => 'Catatan lama',
        ]);

        $response = $this->actingAs($donor->user)->put(route('donor.food-posts.update', $foodPost), $this->validPayload($category, [
            'judul' => 'Judul Baru',
            'jumlah_porsi' => 8,
            'catatan_kurir' => 'Gunakan pintu samping dan hubungi resepsionis.',
        ]));

        $response
            ->assertRedirect(route('donor.food-posts.index'))
            ->assertSessionHasNoErrors();

        $foodPost->refresh();

        $this->assertSame('Judul Baru', $foodPost->judul);
        $this->assertSame('food-posts/old.jpg', $foodPost->foto);
        $this->assertSame(8, $foodPost->jumlah_porsi);
        $this->assertSame(8, $foodPost->sisa_porsi);
        $this->assertSame('Gunakan pintu samping dan hubungi resepsionis.', $foodPost->catatan_kurir);
        Storage::disk('public')->assertExists('food-posts/old.jpg');
    }

    public function test_donor_cannot_update_another_donors_food_post(): void
    {
        [$donor] = $this->donorProfile();
        [$otherDonor] = $this->donorProfile();
        $category = $this->foodCategory();
        $foodPost = $this->foodPost($otherDonor, $category, [
            'judul' => 'Milik Donor Lain',
            'catatan_kurir' => 'Catatan asli pemilik.',
        ]);

        $response = $this->actingAs($donor->user)->put(route('donor.food-posts.update', $foodPost), $this->validPayload($category, [
            'judul' => 'Diubah Paksa',
            'catatan_kurir' => 'Catatan dari donor lain.',
        ]));

        $response->assertNotFound();
        $foodPost->refresh();
        $this->assertSame('Milik Donor Lain', $foodPost->judul);
        $this->assertSame('Catatan asli pemilik.', $foodPost->catatan_kurir);
    }

    public function test_donor_cannot_delete_a_food_post_that_already_has_claims(): void
    {
        [$donor] = $this->donorProfile();
        $category = $this->foodCategory();
        $foodPost = $this->foodPost($donor, $category);
        $recipient = User::factory()->create([
            'role' => Role::Penerima,
            'status_akun' => 'aktif',
        ]);
        $recipientProfile = $recipient->recipientProfile()->create([
            'nama_lembaga' => 'Panti Uji',
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => 'Jl. Panti',
        ]);

        Claim::create([
            'jumlah_porsi' => 1,
            'status' => 'menunggu',
            'tanggal_klaim' => now()->toDateString(),
            'post_id' => $foodPost->id,
            'recipient_id' => $recipientProfile->id,
        ]);

        $response = $this->actingAs($donor->user)->delete(route('donor.food-posts.destroy', $foodPost));

        $response
            ->assertRedirect(route('donor.food-posts.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('food_posts', ['id' => $foodPost->id]);
    }

    public function test_donor_can_delete_a_food_post_without_claims(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('food-posts/delete-me.jpg', 'photo');

        [$donor] = $this->donorProfile();
        $category = $this->foodCategory();
        $foodPost = $this->foodPost($donor, $category, ['foto' => 'food-posts/delete-me.jpg']);

        $response = $this->actingAs($donor->user)->delete(route('donor.food-posts.destroy', $foodPost));

        $response
            ->assertRedirect(route('donor.food-posts.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('food_posts', ['id' => $foodPost->id]);
        Storage::disk('public')->assertMissing('food-posts/delete-me.jpg');
    }

    public function test_recipient_cannot_access_donor_food_post_management(): void
    {
        $recipient = User::factory()->create([
            'role' => Role::Penerima,
            'status_akun' => 'aktif',
        ]);

        $response = $this->actingAs($recipient)->get(route('donor.food-posts.index'));

        $response->assertForbidden();
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
     * @return array<string, mixed>
     */
    private function validPayload(FoodCategory $category, array $overrides = []): array
    {
        return array_merge([
            'judul' => 'Nasi Box',
            'deskripsi' => 'Nasi box siap santap',
            'jumlah_porsi' => 10,
            'alamat' => 'Jl. Merdeka 1',
            'lat' => -6.2,
            'lng' => 106.8,
            'batas_konsumsi' => now()->addDay()->format('Y-m-d H:i:s'),
            'category_id' => $category->id,
        ], $overrides);
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
