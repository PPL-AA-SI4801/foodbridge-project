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
use Database\Seeders\ClaimSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\FoodCategorySeeder;
use Database\Seeders\FoodPostSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class ClaimPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_claim_page_is_only_accessible_to_recipients(): void
    {
        $this->get(route('recipient.claims.index'))->assertRedirect(route('login'));

        $donor = User::factory()->create([
            'role' => Role::Donor,
            'status_akun' => 'aktif',
        ]);
        $administrator = User::factory()->create([
            'role' => Role::Administrator,
            'status_akun' => 'aktif',
        ]);
        [$recipient] = $this->recipientProfile();

        $this->actingAs($donor)
            ->get(route('recipient.claims.index'))
            ->assertForbidden();

        $this->actingAs($administrator)
            ->get(route('recipient.claims.index'))
            ->assertForbidden();

        $this->actingAs($recipient->user)
            ->get(route('recipient.claims.index'))
            ->assertOk()
            ->assertSee('Klaim Saya');
    }

    public function test_recipient_only_sees_their_own_claims(): void
    {
        [$recipient] = $this->recipientProfile();
        [$otherRecipient] = $this->recipientProfile();
        $category = $this->foodCategory();
        $donor = $this->donorProfile(['nama_usaha' => 'Dapur Bersama']);

        $ownPost = $this->foodPost($donor, $category, ['judul' => 'Nasi Box Milik Sendiri']);
        $otherPost = $this->foodPost($donor, $category, ['judul' => 'Nasi Box Milik Penerima Lain']);

        $this->claim($recipient, $ownPost);
        $this->claim($otherRecipient, $otherPost);

        $this->actingAs($recipient->user)
            ->get(route('recipient.claims.index'))
            ->assertOk()
            ->assertSee('Nasi Box Milik Sendiri')
            ->assertDontSee('Nasi Box Milik Penerima Lain');
    }

    public function test_claims_can_be_searched_by_food_or_donor_name_and_filtered_by_status(): void
    {
        [$recipient] = $this->recipientProfile();
        $category = $this->foodCategory();
        $firstDonor = $this->donorProfile(['nama_usaha' => 'Dapur Sehat']);
        $secondDonor = $this->donorProfile(['nama_usaha' => 'Warung Berkah']);

        $pendingPost = $this->foodPost($firstDonor, $category, ['judul' => 'Nasi Ayam Bakar']);
        $completedPost = $this->foodPost($secondDonor, $category, ['judul' => 'Sup Sayur Segar']);

        $this->claim($recipient, $pendingPost, ['status' => 'pending']);
        $this->claim($recipient, $completedPost, ['status' => 'completed']);

        $this->actingAs($recipient->user)
            ->get(route('recipient.claims.index', ['q' => 'Ayam']))
            ->assertOk()
            ->assertSee('Nasi Ayam Bakar')
            ->assertDontSee('Sup Sayur Segar');

        $this->actingAs($recipient->user)
            ->get(route('recipient.claims.index', ['q' => 'Warung Berkah']))
            ->assertOk()
            ->assertSee('Sup Sayur Segar')
            ->assertDontSee('Nasi Ayam Bakar');

        $this->actingAs($recipient->user)
            ->get(route('recipient.claims.index', ['status' => 'selesai']))
            ->assertOk()
            ->assertSee('Sup Sayur Segar')
            ->assertDontSee('Nasi Ayam Bakar');
    }

    public function test_daily_quota_ignores_rejected_claims(): void
    {
        [$recipient] = $this->recipientProfile();
        $category = $this->foodCategory();
        $donor = $this->donorProfile();

        $this->claim($recipient, $this->foodPost($donor, $category), [
            'status' => 'pending',
            'tanggal_klaim' => today(),
        ]);
        $this->claim($recipient, $this->foodPost($donor, $category), [
            'status' => 'rejected',
            'tanggal_klaim' => today(),
        ]);

        $this->actingAs($recipient->user)
            ->get(route('recipient.claims.index'))
            ->assertOk()
            ->assertSee('data-quota="2/3"', false)
            ->assertSee('1 dari 3 klaim sudah terpakai hari ini.');
    }

    public function test_claim_page_shows_a_distinct_empty_state_without_claims_and_when_filters_match_nothing(): void
    {
        [$recipient] = $this->recipientProfile();
        $category = $this->foodCategory();
        $donor = $this->donorProfile();

        $this->actingAs($recipient->user)
            ->get(route('recipient.claims.index'))
            ->assertOk()
            ->assertSee('Belum ada klaim makanan')
            ->assertSee('Buka katalog makanan')
            ->assertSee(route('recipient.catalog.index'), false);

        $this->claim($recipient, $this->foodPost($donor, $category), ['status' => 'pending']);

        $this->actingAs($recipient->user)
            ->get(route('recipient.claims.index', ['status' => 'ditolak']))
            ->assertOk()
            ->assertSee('Tidak ada klaim yang cocok')
            ->assertSee('Tampilkan semua klaim')
            ->assertDontSee('Belum ada klaim makanan');
    }

    public function test_claim_page_paginates_ten_rows_and_keeps_query_string_filters(): void
    {
        [$recipient] = $this->recipientProfile();
        $category = $this->foodCategory();
        $donor = $this->donorProfile();

        foreach (range(1, 12) as $index) {
            $post = $this->foodPost($donor, $category, [
                'judul' => sprintf('Klaim Halaman %02d', $index),
            ]);

            $this->claim($recipient, $post, [
                'status' => 'pending',
                'tanggal_klaim' => today(),
            ]);
        }

        $this->actingAs($recipient->user)
            ->get(route('recipient.claims.index', ['status' => 'menunggu']))
            ->assertOk()
            ->assertSee('Klaim Halaman 12')
            ->assertDontSee('Klaim Halaman 02')
            ->assertSee('status=menunggu', false);

        $this->actingAs($recipient->user)
            ->get(route('recipient.claims.index', ['status' => 'menunggu', 'page' => 2]))
            ->assertOk()
            ->assertSee('Klaim Halaman 02')
            ->assertSee('Klaim Halaman 01');
    }

    public function test_store_still_rejects_a_new_claim_when_the_daily_quota_is_exhausted(): void
    {
        [$recipient] = $this->recipientProfile();
        $category = $this->foodCategory();
        $donor = $this->donorProfile();

        foreach (['pending', 'approved', 'completed'] as $status) {
            $this->claim($recipient, $this->foodPost($donor, $category), [
                'status' => $status,
                'tanggal_klaim' => today(),
            ]);
        }

        $newPost = $this->foodPost($donor, $category, [
            'judul' => 'Makanan Baru Setelah Kuota Habis',
            'batas_konsumsi' => now()->addDay(),
            'sisa_porsi' => 5,
        ]);

        $this->actingAs($recipient->user)
            ->from(route('recipient.catalog.index'))
            ->post(route('recipient.claims.store', $newPost), ['jumlah_porsi' => 1])
            ->assertRedirect(route('recipient.catalog.index'))
            ->assertSessionHasErrors('jumlah_porsi');

        $this->assertDatabaseCount('claims', 3);
    }

    public function test_claim_seeder_is_idempotent_and_covers_every_demo_status(): void
    {
        $this->seed([
            FoodCategorySeeder::class,
            DemoUserSeeder::class,
            FoodPostSeeder::class,
            ClaimSeeder::class,
        ]);

        $demoRecipient = User::query()
            ->where('email', 'penerima@foodbridge.test')
            ->firstOrFail()
            ->recipientProfile;

        $this->assertGreaterThan(10, Claim::query()->where('recipient_id', $demoRecipient->id)->count());
        $this->assertDatabaseHas('claims', ['recipient_id' => $demoRecipient->id, 'status' => 'pending']);
        $this->assertDatabaseHas('claims', ['recipient_id' => $demoRecipient->id, 'status' => 'approved', 'metode_pengambilan' => 'ambil_sendiri']);
        $this->assertDatabaseHas('claims', ['recipient_id' => $demoRecipient->id, 'status' => 'approved', 'metode_pengambilan' => 'kurir_online']);
        $this->assertDatabaseHas('claims', ['recipient_id' => $demoRecipient->id, 'status' => 'completed']);
        $this->assertDatabaseHas('claims', ['recipient_id' => $demoRecipient->id, 'status' => 'rejected']);

        $claimCount = Claim::query()->count();

        $this->seed(ClaimSeeder::class);

        $this->assertSame($claimCount, Claim::query()->count());
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
            'alamat' => 'Jl. Penerima No. 2, Jakarta',
            'lat' => -6.2,
            'lng' => 106.8,
        ], $overrides));

        return [$profile, $user];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function donorProfile(array $overrides = []): DonorProfile
    {
        $user = User::factory()->create([
            'role' => Role::Donor,
            'status_akun' => 'aktif',
        ]);

        return $user->donorProfile()->create(array_merge([
            'nama_usaha' => fake()->unique()->company(),
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor No. 1, Bandung',
            'lat' => -6.2,
            'lng' => 106.8,
        ], $overrides));
    }

    private function foodCategory(string $name = 'Karbohidrat'): FoodCategory
    {
        return FoodCategory::create(['nama_kategori' => $name]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function foodPost(DonorProfile $donor, FoodCategory $category, array $overrides = []): FoodPost
    {
        return FoodPost::create(array_merge([
            'judul' => 'Nasi Box',
            'deskripsi' => 'Nasi box siap santap',
            'foto' => 'seed/placeholder.webp',
            'jumlah_porsi' => 10,
            'sisa_porsi' => 10,
            'alamat' => $donor->alamat,
            'lat' => $donor->lat,
            'lng' => $donor->lng,
            'batas_konsumsi' => now()->addDay(),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donor->id,
            'category_id' => $category->id,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function claim(RecipientProfile $recipient, FoodPost $post, array $overrides = []): Claim
    {
        return Claim::create(array_merge([
            'jumlah_porsi' => 1,
            'status' => 'pending',
            'metode_pengambilan' => null,
            'kode_verifikasi' => null,
            'tanggal_klaim' => today(),
            'approved_at' => null,
            'completed_at' => null,
            'post_id' => $post->id,
            'recipient_id' => $recipient->id,
        ], $overrides));
    }
}
