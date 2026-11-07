<?php

namespace Tests\Feature;

use App\Enums\FoodPostStatus;
use App\Enums\FoodRequestStatus;
use App\Enums\Role;
use App\Enums\UrgencyLevel;
use App\Models\Claim;
use App\Models\DonorProfile;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\FoodRequest;
use App\Models\InAppNotification;
use App\Models\RecipientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class RequestBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_penerima_can_create_food_request_and_notifies_all_verified_donors(): void
    {
        [$recipientUser, $recipientProfile] = $this->recipientProfile();
        [$donorUser1] = $this->donorProfile();
        [$donorUser2] = $this->donorProfile();
        $unverifiedDonor = User::factory()->create([
            'role' => Role::Donor,
            'status_akun' => 'menunggu',
        ]);
        $category = $this->foodCategory();

        $response = $this->actingAs($recipientUser)->post(route('recipient.requests.store'), [
            'judul' => 'Nasi Kotak untuk Anak Panti',
            'deskripsi' => 'Butuh 30 porsi makan malam tanpa pedas.',
            'category_id' => $category->id,
            'jumlah_porsi' => 30,
            'urgency_level' => UrgencyLevel::Darurat->value,
            'metode_penerimaan' => 'diantar_kurir',
            'batas_waktu' => now()->addHours(5)->format('Y-m-d H:i:s'),
        ]);

        $response
            ->assertRedirect(route('recipient.requests.index'))
            ->assertSessionHasNoErrors();

        $foodRequest = FoodRequest::query()->sole();
        $this->assertSame($recipientProfile->id, $foodRequest->recipient_id);
        $this->assertSame($category->id, $foodRequest->category_id);
        $this->assertSame('Nasi Kotak untuk Anak Panti', $foodRequest->judul);
        $this->assertSame(30, $foodRequest->jumlah_porsi);
        $this->assertSame(UrgencyLevel::Darurat, $foodRequest->urgency_level);
        $this->assertSame('diantar_kurir', $foodRequest->metode_penerimaan);
        $this->assertSame(FoodRequestStatus::Open, $foodRequest->status);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $donorUser1->id,
            'tipe' => 'request',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $donorUser2->id,
            'tipe' => 'request',
        ]);
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $unverifiedDonor->id,
        ]);
    }

    public function test_penerima_cannot_exceed_three_simultaneous_open_requests(): void
    {
        [$recipientUser, $recipientProfile] = $this->recipientProfile();
        $category = $this->foodCategory();

        for ($i = 1; $i <= 3; $i++) {
            FoodRequest::create([
                'recipient_id' => $recipientProfile->id,
                'category_id' => $category->id,
                'judul' => "Permintaan {$i}",
                'jumlah_porsi' => 10,
                'urgency_level' => UrgencyLevel::Mendesak,
                'metode_penerimaan' => 'fleksibel',
                'batas_waktu' => now()->addHours(4),
                'status' => FoodRequestStatus::Open,
            ]);
        }

        $response = $this->actingAs($recipientUser)->post(route('recipient.requests.store'), [
            'judul' => 'Permintaan Keempat',
            'category_id' => $category->id,
            'jumlah_porsi' => 15,
            'urgency_level' => UrgencyLevel::Normal->value,
            'metode_penerimaan' => 'fleksibel',
            'batas_waktu' => now()->addHours(4)->format('Y-m-d H:i:s'),
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('food_requests', 3);
    }

    public function test_overdue_open_requests_are_automatically_marked_expired(): void
    {
        [$donorUser] = $this->donorProfile();
        [, $recipientProfile] = $this->recipientProfile();
        $category = $this->foodCategory();

        $overdueRequest = FoodRequest::create([
            'recipient_id' => $recipientProfile->id,
            'category_id' => $category->id,
            'judul' => 'Permintaan Sudah Lewat Tenggat',
            'jumlah_porsi' => 20,
            'urgency_level' => UrgencyLevel::Mendesak,
            'metode_penerimaan' => 'fleksibel',
            'batas_waktu' => now()->subHour(),
            'status' => FoodRequestStatus::Open,
        ]);

        $activeRequest = FoodRequest::create([
            'recipient_id' => $recipientProfile->id,
            'category_id' => $category->id,
            'judul' => 'Permintaan Masih Aktif',
            'jumlah_porsi' => 20,
            'urgency_level' => UrgencyLevel::Darurat,
            'metode_penerimaan' => 'fleksibel',
            'batas_waktu' => now()->addHours(3),
            'status' => FoodRequestStatus::Open,
        ]);

        $response = $this->actingAs($donorUser)->get(route('donor.requests.index'));

        $response
            ->assertOk()
            ->assertSee('Permintaan Masih Aktif')
            ->assertDontSee('Permintaan Sudah Lewat Tenggat');

        $overdueRequest->refresh();
        $this->assertSame(FoodRequestStatus::Expired, $overdueRequest->status);
    }

    public function test_donor_can_fulfil_request_with_existing_food_post_and_creates_approved_claim(): void
    {
        [$donorUser, $donorProfile] = $this->donorProfile();
        [$recipientUser, $recipientProfile] = $this->recipientProfile();
        $category = $this->foodCategory();

        $foodPost = FoodPost::create([
            'judul' => 'Nasi Box Ayam Bakar',
            'deskripsi' => 'Siap santap',
            'foto' => 'food-posts/test.jpg',
            'jumlah_porsi' => 40,
            'sisa_porsi' => 40,
            'alamat' => 'Jl. Donor 1',
            'lat' => -6.2,
            'lng' => 106.8,
            'batas_konsumsi' => now()->addHours(8),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donorProfile->id,
            'category_id' => $category->id,
        ]);

        $foodRequest = FoodRequest::create([
            'recipient_id' => $recipientProfile->id,
            'category_id' => $category->id,
            'judul' => 'Kebutuhan Makan Siang Panti',
            'jumlah_porsi' => 25,
            'urgency_level' => UrgencyLevel::Darurat,
            'metode_penerimaan' => 'ambil_sendiri',
            'batas_waktu' => now()->addHours(5),
            'status' => FoodRequestStatus::Open,
        ]);

        $response = $this->actingAs($donorUser)->post(route('donor.requests.fulfil', $foodRequest), [
            'post_id' => $foodPost->id,
        ]);

        $response
            ->assertRedirect(route('donor.requests.index', ['view' => 'mine']))
            ->assertSessionHas('status');

        $foodRequest->refresh();
        $foodPost->refresh();

        $this->assertSame(FoodRequestStatus::InProgress, $foodRequest->status);
        $this->assertSame($donorProfile->id, $foodRequest->donor_id);
        $this->assertNotNull($foodRequest->claim_id);
        $this->assertSame(15, $foodPost->sisa_porsi);

        $claim = Claim::findOrFail($foodRequest->claim_id);
        $this->assertSame('approved', $claim->status);
        $this->assertSame(25, $claim->jumlah_porsi);
        $this->assertSame($recipientProfile->id, $claim->recipient_id);
        $this->assertNotNull($claim->kode_verifikasi);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $recipientUser->id,
            'tipe' => 'request',
        ]);

        // Recipient marks request as fulfilled
        $this->actingAs($recipientUser)
            ->patch(route('recipient.requests.complete', $foodRequest))
            ->assertRedirect(route('recipient.requests.index'));

        $foodRequest->refresh();
        $claim->refresh();

        $this->assertSame(FoodRequestStatus::Fulfilled, $foodRequest->status);
        $this->assertSame('completed', $claim->status);
    }

    public function test_administrator_can_monitor_and_moderate_requests(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Administrator,
            'status_akun' => 'aktif',
        ]);
        [$recipientUser, $recipientProfile] = $this->recipientProfile();
        $category = $this->foodCategory();

        $foodRequest = FoodRequest::create([
            'recipient_id' => $recipientProfile->id,
            'category_id' => $category->id,
            'judul' => 'Permintaan Untuk Dimoderasi',
            'jumlah_porsi' => 50,
            'urgency_level' => UrgencyLevel::Mendesak,
            'metode_penerimaan' => 'fleksibel',
            'batas_waktu' => now()->addHours(6),
            'status' => FoodRequestStatus::Open,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.requests.index'))
            ->assertOk()
            ->assertSee('Permintaan Untuk Dimoderasi');

        $this->actingAs($admin)
            ->patch(route('admin.requests.cancel', $foodRequest))
            ->assertRedirect();

        $foodRequest->refresh();
        $this->assertSame(FoodRequestStatus::Cancelled, $foodRequest->status);

        $this->actingAs($admin)
            ->delete(route('admin.requests.destroy', $foodRequest))
            ->assertRedirect();

        $this->assertDatabaseMissing('food_requests', ['id' => $foodRequest->id]);
        $this->assertTrue(
            InAppNotification::where('user_id', $recipientUser->id)->where('tipe', 'request')->exists()
        );
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
            'nama_usaha' => fake()->unique()->company(),
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
            'nama_lembaga' => fake()->unique()->company(),
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => 'Jl. Penerima',
            'lat' => -6.21,
            'lng' => 106.81,
        ]);

        return [$user, $profile];
    }

    private function foodCategory(): FoodCategory
    {
        return FoodCategory::create([
            'nama_kategori' => fake()->unique()->word(),
        ]);
    }
}
