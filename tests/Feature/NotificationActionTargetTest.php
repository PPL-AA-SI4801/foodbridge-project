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
class NotificationActionTargetTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_recipient_claim_notification_opens_only_the_related_claim(): void
    {
        [$recipientUser, $recipient] = $this->recipientWithProfile();
        [, $donor] = $this->donorWithProfile();

        $selectedClaim = $this->claimFor($recipient, $this->foodPostFor($donor, 'Nasi Box Yang Dibuka'));
        $otherClaim = $this->claimFor($recipient, $this->foodPostFor($donor, 'Nasi Box Lainnya'));

        $notification = InAppNotification::create([
            'user_id' => $recipientUser->id,
            'judul' => 'Klaim Disetujui',
            'pesan' => 'Klaim Anda telah disetujui.',
            'tipe' => 'klaim',
        ]);

        $this->actingAs($recipientUser)
            ->get(route('notifications.open', $notification))
            ->assertRedirect(route('recipient.claims.index'));

        $this->actingAs($recipientUser)
            ->get(route('recipient.claims.index', ['claim' => $selectedClaim->id]))
            ->assertOk()
            ->assertSee('Menampilkan klaim yang Anda buka dari notifikasi.')
            ->assertSee($selectedClaim->post->judul)
            ->assertDontSee($otherClaim->post->judul)
            ->assertSee('claim-detail-'.$selectedClaim->id, false);
    }

    public function test_a_donor_claim_notification_opens_only_the_related_claim(): void
    {
        [$donorUser, $donor] = $this->donorWithProfile();
        [, $recipient] = $this->recipientWithProfile();

        $selectedClaim = $this->claimFor($recipient, $this->foodPostFor($donor, 'Nasi Box Klaim Dipilih'));
        $otherClaim = $this->claimFor($recipient, $this->foodPostFor($donor, 'Nasi Box Klaim Lain'));

        $this->actingAs($donorUser)
            ->get(route('donor.claims.index', ['claim' => $selectedClaim->id]))
            ->assertOk()
            ->assertSee('Menampilkan klaim yang Anda buka dari notifikasi.')
            ->assertSee($selectedClaim->post->judul)
            ->assertDontSee($otherClaim->post->judul)
            ->assertSee('id="claim-'.$selectedClaim->id.'"', false);
    }

    public function test_a_recipient_request_notification_opens_only_the_related_request(): void
    {
        [$recipientUser, $recipient] = $this->recipientWithProfile();

        $selectedRequest = $this->foodRequestFor($recipient, 'Kebutuhan Yang Dibuka');
        $otherRequest = $this->foodRequestFor($recipient, 'Kebutuhan Lainnya');

        $this->actingAs($recipientUser)
            ->get(route('recipient.requests.index', ['request' => $selectedRequest->id]))
            ->assertOk()
            ->assertSee('Menampilkan permintaan kebutuhan yang Anda buka dari notifikasi.')
            ->assertSee($selectedRequest->judul)
            ->assertDontSee($otherRequest->judul)
            ->assertSee('id="request-'.$selectedRequest->id.'"', false);
    }

    public function test_a_donor_request_notification_opens_only_the_related_request(): void
    {
        [$donorUser] = $this->donorWithProfile();
        [, $recipient] = $this->recipientWithProfile();

        $selectedRequest = $this->foodRequestFor($recipient, 'Kebutuhan Donatur Dibuka');
        $otherRequest = $this->foodRequestFor($recipient, 'Kebutuhan Donatur Lain');

        $this->actingAs($donorUser)
            ->get(route('donor.requests.index', ['request' => $selectedRequest->id]))
            ->assertOk()
            ->assertSee('Menampilkan permintaan kebutuhan yang Anda buka dari notifikasi.')
            ->assertSee($selectedRequest->judul)
            ->assertDontSee($otherRequest->judul)
            ->assertSee('id="request-'.$selectedRequest->id.'"', false);
    }

    public function test_notification_open_maps_each_type_to_its_fresh_page(): void
    {
        [$donorUser] = $this->donorWithProfile();
        [$recipientUser] = $this->recipientWithProfile();
        $admin = User::factory()->create(['role' => Role::Administrator, 'status_akun' => 'aktif']);

        $cases = [
            [$donorUser, 'klaim', route('donor.claims.index')],
            [$recipientUser, 'klaim', route('recipient.claims.index')],
            [$donorUser, 'request', route('donor.requests.index')],
            [$recipientUser, 'request', route('recipient.requests.index')],
            [$donorUser, 'post', route('donor.food-posts.index')],
            [$admin, 'akun', route('admin.verifications.index')],
            [$donorUser, 'laporan', route('notifications.index')],
        ];

        foreach ($cases as [$user, $tipe, $expected]) {
            $notification = InAppNotification::create([
                'user_id' => $user->id,
                'judul' => 'Uji pemetaan notifikasi',
                'pesan' => 'Klik notifikasi harus membuka halaman fresh.',
                'tipe' => $tipe,
            ]);

            $this->actingAs($user)
                ->get(route('notifications.open', $notification))
                ->assertRedirect($expected);

            $this->assertTrue($notification->fresh()->is_read);
        }
    }

    /**
     * @return array{0: User, 1: DonorProfile}
     */
    private function donorWithProfile(): array
    {
        $user = User::factory()->create([
            'role' => Role::Donor,
            'status_akun' => 'aktif',
        ]);

        $profile = $user->donorProfile()->create([
            'nama_usaha' => 'Donor Uji '.fake()->unique()->numerify('###'),
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor Uji No. 1',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        return [$user, $profile];
    }

    /**
     * @return array{0: User, 1: RecipientProfile}
     */
    private function recipientWithProfile(): array
    {
        $user = User::factory()->create([
            'role' => Role::Penerima,
            'status_akun' => 'aktif',
        ]);

        $profile = $user->recipientProfile()->create([
            'nama_lembaga' => 'Panti Uji '.fake()->unique()->numerify('###'),
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => 'Jl. Penerima Uji No. 2',
            'lat' => -6.21,
            'lng' => 106.81,
        ]);

        return [$user, $profile];
    }

    private function foodPostFor(DonorProfile $donor, string $title): FoodPost
    {
        $category = FoodCategory::create([
            'nama_kategori' => 'Kategori '.fake()->unique()->numerify('###'),
        ]);

        return FoodPost::create([
            'judul' => $title,
            'deskripsi' => 'Makanan untuk pengujian tautan notifikasi.',
            'foto' => 'food-posts/sample.jpg',
            'jumlah_porsi' => 20,
            'sisa_porsi' => 20,
            'alamat' => 'Jl. Donor Uji No. 1',
            'lat' => -6.2,
            'lng' => 106.8,
            'batas_konsumsi' => now()->addHours(8),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donor->id,
            'category_id' => $category->id,
        ]);
    }

    private function claimFor(RecipientProfile $recipient, FoodPost $post): Claim
    {
        return Claim::create([
            'post_id' => $post->id,
            'recipient_id' => $recipient->id,
            'jumlah_porsi' => 1,
            'status' => 'approved',
            'tanggal_klaim' => today(),
        ]);
    }

    private function foodRequestFor(RecipientProfile $recipient, string $title): FoodRequest
    {
        $category = FoodCategory::create([
            'nama_kategori' => 'Kategori '.fake()->unique()->numerify('###'),
        ]);

        return FoodRequest::create([
            'recipient_id' => $recipient->id,
            'category_id' => $category->id,
            'judul' => $title,
            'deskripsi' => 'Kebutuhan untuk pengujian tautan notifikasi.',
            'jumlah_porsi' => 10,
            'urgency_level' => UrgencyLevel::Mendesak,
            'metode_penerimaan' => 'fleksibel',
            'batas_waktu' => now()->addHours(8),
            'status' => FoodRequestStatus::Open,
        ]);
    }
}
