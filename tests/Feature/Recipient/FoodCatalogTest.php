<?php

namespace Tests\Feature\Recipient;

use App\Enums\FoodPostStatus;
use App\Enums\Role;
use App\Models\Claim;
use App\Models\DonorProfile;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\RecipientProfile;
use App\Models\User;
use Database\Seeders\FoodCategorySeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class FoodCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_recipient_can_view_the_food_catalog(): void
    {
        [$recipient] = $this->recipientProfile();
        $category = $this->foodCategory('Karbohidrat');
        $donor = $this->donorProfile();
        $foodPost = $this->foodPost($donor, $category, ['judul' => 'Nasi Box Tersedia']);

        $response = $this->actingAs($recipient->user)->get(route('recipient.catalog.index'));

        $response
            ->assertOk()
            ->assertSee('Katalog Peta Pangan')
            ->assertSee('Peta pangan')
            ->assertSee($foodPost->judul);
    }

    public function test_catalog_only_lists_available_food_posts(): void
    {
        [$recipient] = $this->recipientProfile();
        $category = $this->foodCategory('Protein');
        $donor = $this->donorProfile();

        $available = $this->foodPost($donor, $category, ['judul' => 'Ayam Tersedia']);
        $expired = $this->foodPost($donor, $category, [
            'judul' => 'Ayam Lewat Waktu',
            'batas_konsumsi' => now()->subMinute(),
        ]);
        $soldOut = $this->foodPost($donor, $category, [
            'judul' => 'Ayam Habis',
            'status' => FoodPostStatus::Habis,
        ]);
        $empty = $this->foodPost($donor, $category, [
            'judul' => 'Ayam Tanpa Porsi',
            'sisa_porsi' => 0,
        ]);

        $response = $this->actingAs($recipient->user)->get(route('recipient.catalog.index'));

        $response->assertOk()->assertSee($available->judul);

        foreach ([$expired, $soldOut, $empty] as $hiddenPost) {
            $response->assertDontSee($hiddenPost->judul);
        }
    }

    public function test_catalog_can_be_filtered_by_nutrition_category(): void
    {
        [$recipient] = $this->recipientProfile();
        $protein = $this->foodCategory('Protein');
        $vegetables = $this->foodCategory('Sayuran');
        $donor = $this->donorProfile();
        $proteinPost = $this->foodPost($donor, $protein, ['judul' => 'Telur Balado']);
        $vegetablePost = $this->foodPost($donor, $vegetables, ['judul' => 'Sup Bayam']);

        $response = $this->actingAs($recipient->user)->get(route('recipient.catalog.index', [
            'category_id' => $protein->id,
        ]));

        $response
            ->assertOk()
            ->assertSee($proteinPost->judul)
            ->assertDontSee($vegetablePost->judul)
            ->assertSee('Atur ulang');
    }

    public function test_catalog_can_be_searched_by_food_donor_or_location(): void
    {
        [$recipient] = $this->recipientProfile();
        $category = $this->foodCategory('Protein');
        $donor = $this->donorProfile();
        $otherDonor = $this->donorProfile();
        $otherDonor->update(['nama_usaha' => 'Dapur Berkah']);

        $matchingTitle = $this->foodPost($donor, $category, [
            'judul' => 'Nasi Ayam Bakar',
            'alamat' => 'Jl. Melati',
        ]);
        $matchingDonor = $this->foodPost($otherDonor, $category, [
            'judul' => 'Sup Sayur',
            'alamat' => 'Jl. Kenanga',
        ]);
        $matchingAddress = $this->foodPost($donor, $category, [
            'judul' => 'Buah Potong',
            'alamat' => 'Gedung Serbaguna Melati',
        ]);
        $notMatching = $this->foodPost($donor, $category, [
            'judul' => 'Roti Gandum',
            'alamat' => 'Jl. Anggrek',
        ]);

        $this->actingAs($recipient->user)
            ->get(route('recipient.catalog.index', ['q' => 'ayam']))
            ->assertOk()
            ->assertSee($matchingTitle->judul)
            ->assertDontSee($matchingDonor->judul)
            ->assertDontSee($notMatching->judul);

        $this->actingAs($recipient->user)
            ->get(route('recipient.catalog.index', ['q' => 'Dapur Berkah']))
            ->assertOk()
            ->assertSee($matchingDonor->judul)
            ->assertDontSee($notMatching->judul);

        $this->actingAs($recipient->user)
            ->get(route('recipient.catalog.index', ['q' => 'Melati']))
            ->assertOk()
            ->assertSee($matchingTitle->judul)
            ->assertSee($matchingAddress->judul)
            ->assertDontSee($notMatching->judul);
    }

    public function test_catalog_can_be_filtered_by_radius(): void
    {
        [$recipient] = $this->recipientProfile(['lat' => -6.2, 'lng' => 106.8]);
        $category = $this->foodCategory('Karbohidrat');
        $donor = $this->donorProfile();

        $nearPost = $this->foodPost($donor, $category, [
            'judul' => 'Nasi Dalam Radius',
            'lat' => -6.21,
            'lng' => 106.81,
        ]);
        $farPost = $this->foodPost($donor, $category, [
            'judul' => 'Nasi Luar Radius',
            'lat' => -6.4,
            'lng' => 107.0,
        ]);

        $response = $this->actingAs($recipient->user)->get(route('recipient.catalog.index', [
            'radius_km' => 5,
        ]));

        $response
            ->assertOk()
            ->assertSee($nearPost->judul)
            ->assertDontSee($farPost->judul)
            ->assertSee('Radius 5 km');
    }

    public function test_radius_filter_is_ignored_without_recipient_location(): void
    {
        [$recipient] = $this->recipientProfile(['lat' => null, 'lng' => null]);
        $category = $this->foodCategory('Karbohidrat');
        $donor = $this->donorProfile();
        $foodPost = $this->foodPost($donor, $category, [
            'judul' => 'Nasi Tetap Tampil',
            'lat' => -6.4,
            'lng' => 107.0,
        ]);

        $response = $this->actingAs($recipient->user)->get(route('recipient.catalog.index', [
            'radius_km' => 5,
        ]));

        $response
            ->assertOk()
            ->assertSee($foodPost->judul)
            ->assertDontSee('Radius 5 km');
    }

    public function test_catalog_header_uses_real_daily_claim_data(): void
    {
        [$recipient] = $this->recipientProfile();
        $category = $this->foodCategory('Protein');
        $donor = $this->donorProfile();
        $foodPost = $this->foodPost($donor, $category);

        Claim::create([
            'jumlah_porsi' => 4,
            'status' => 'menunggu',
            'tanggal_klaim' => now()->toDateString(),
            'post_id' => $foodPost->id,
            'recipient_id' => $recipient->id,
        ]);

        $this->actingAs($recipient->user)
            ->get(route('recipient.catalog.index'))
            ->assertOk()
            ->assertSee('Klaim hari ini')
            ->assertSee('4 porsi');
    }

    public function test_catalog_orders_posts_by_nearest_distance(): void
    {
        [$recipient] = $this->recipientProfile(['lat' => -6.2, 'lng' => 106.8]);
        $category = $this->foodCategory('Karbohidrat');
        $donor = $this->donorProfile();

        $farPost = $this->foodPost($donor, $category, [
            'judul' => 'Nasi Paling Jauh',
            'lat' => -6.4,
            'lng' => 107.0,
            'batas_konsumsi' => now()->addHour(),
        ]);
        $nearPost = $this->foodPost($donor, $category, [
            'judul' => 'Nasi Paling Dekat',
            'lat' => -6.21,
            'lng' => 106.81,
            'batas_konsumsi' => now()->addHours(4),
        ]);

        $response = $this->actingAs($recipient->user)->get(route('recipient.catalog.index'));

        $response
            ->assertOk()
            ->assertSeeInOrder([$nearPost->judul, $farPost->judul]);
    }

    public function test_catalog_falls_back_to_deadline_when_recipient_has_no_location(): void
    {
        [$recipient] = $this->recipientProfile(['lat' => null, 'lng' => null]);
        $category = $this->foodCategory('Sayuran');
        $donor = $this->donorProfile();

        $laterPost = $this->foodPost($donor, $category, [
            'judul' => 'Sayur Batas Lama',
            'batas_konsumsi' => now()->addHours(4),
        ]);
        $earlierPost = $this->foodPost($donor, $category, [
            'judul' => 'Sayur Batas Dekat',
            'batas_konsumsi' => now()->addHour(),
        ]);

        $response = $this->actingAs($recipient->user)->get(route('recipient.catalog.index'));

        $response
            ->assertOk()
            ->assertSee('Lokasi lembaga belum diatur.')
            ->assertSeeInOrder([$earlierPost->judul, $laterPost->judul]);
    }

    public function test_catalog_is_only_accessible_to_active_recipients(): void
    {
        $this->get(route('recipient.catalog.index'))->assertRedirect(route('login'));

        $donor = User::factory()->create([
            'role' => Role::Donor,
            'status_akun' => 'aktif',
        ]);
        $administrator = User::factory()->create([
            'role' => Role::Administrator,
            'status_akun' => 'aktif',
        ]);
        $inactiveRecipient = User::factory()->create([
            'role' => Role::Penerima,
            'status_akun' => 'menunggu',
        ]);

        $this->actingAs($donor)
            ->get(route('recipient.catalog.index'))
            ->assertForbidden();

        $this->actingAs($administrator)
            ->get(route('recipient.catalog.index'))
            ->assertForbidden();

        $this->actingAs($inactiveRecipient)
            ->get(route('recipient.catalog.index'))
            ->assertRedirect(route('login'));
    }

    public function test_expiry_command_marks_posts_that_passed_their_deadline(): void
    {
        $category = $this->foodCategory('Protein');
        $donor = $this->donorProfile();

        $availablePast = $this->foodPost($donor, $category, [
            'judul' => 'Belum Ditandai',
            'batas_konsumsi' => now()->subMinute(),
        ]);
        $soldOutPast = $this->foodPost($donor, $category, [
            'judul' => 'Habis Lewat Waktu',
            'status' => FoodPostStatus::Habis,
            'batas_konsumsi' => now()->subMinute(),
        ]);
        $futurePost = $this->foodPost($donor, $category, [
            'judul' => 'Masih Aman',
            'batas_konsumsi' => now()->addHour(),
        ]);
        $alreadyExpired = $this->foodPost($donor, $category, [
            'judul' => 'Sudah Kedaluwarsa',
            'status' => FoodPostStatus::Kedaluwarsa,
            'batas_konsumsi' => now()->subHour(),
        ]);

        $this->artisan('food-posts:expire')->assertSuccessful();

        $this->assertSame(FoodPostStatus::Kedaluwarsa, $availablePast->fresh()->status);
        $this->assertSame(FoodPostStatus::Kedaluwarsa, $soldOutPast->fresh()->status);
        $this->assertSame(FoodPostStatus::Tersedia, $futurePost->fresh()->status);
        $this->assertSame(FoodPostStatus::Kedaluwarsa, $alreadyExpired->fresh()->status);
    }

    public function test_expiry_command_is_scheduled_every_minute(): void
    {
        $event = collect($this->app->make(Schedule::class)->events())
            ->first(fn ($event) => str_contains((string) $event->command, 'food-posts:expire'));

        $this->assertNotNull($event);
        $this->assertSame('* * * * *', $event->expression);
    }

    public function test_food_category_seeder_is_idempotent(): void
    {
        $this->seed(FoodCategorySeeder::class);
        $this->seed(FoodCategorySeeder::class);

        $this->assertDatabaseCount('food_categories', 6);
        $this->assertDatabaseHas('food_categories', ['nama_kategori' => 'Karbohidrat']);
        $this->assertDatabaseHas('food_categories', ['nama_kategori' => 'Protein']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{0: RecipientProfile, 1: User}
     */
    private function recipientProfile(array $overrides = []): array
    {
        $user = User::factory()->create([
            'role' => Role::Penerima,
            'status_akun' => 'aktif',
        ]);

        $profile = $user->recipientProfile()->create(array_merge([
            'nama_lembaga' => fake()->unique()->company(),
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => 'Jl. Penerima',
            'lat' => -6.2,
            'lng' => 106.8,
        ], $overrides));

        return [$profile, $user];
    }

    private function donorProfile(): DonorProfile
    {
        $user = User::factory()->create([
            'role' => Role::Donor,
            'status_akun' => 'aktif',
        ]);

        return $user->donorProfile()->create([
            'nama_usaha' => fake()->unique()->company(),
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);
    }

    private function foodCategory(string $name): FoodCategory
    {
        return FoodCategory::create([
            'nama_kategori' => $name,
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
