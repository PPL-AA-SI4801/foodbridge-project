<?php

namespace Tests\Feature;

use App\Enums\FoodPostStatus;
use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Models\Claim;
use App\Models\DonorProfile;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\InAppNotification;
use App\Models\RecipientProfile;
use App\Models\User;
use App\Models\VerificationDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class NotificationAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ts_pbi8_01_penerima_mengajukan_klaim_membuat_notifikasi_dan_menambah_lonceng_donor(): void
    {
        [$recipientUser, $recipientProfile] = $this->recipientWithProfile();
        [$donorUser, $donorProfile] = $this->donorWithProfile();
        $foodPost = $this->foodPostFor($donorProfile);

        $this->actingAs($recipientUser)
            ->post(route('recipient.claims.store', $foodPost), ['jumlah_porsi' => 2])
            ->assertRedirect(route('recipient.claims.index'));

        $notification = InAppNotification::query()
            ->where('user_id', $donorUser->id)
            ->sole();

        $this->assertSame('claim', $notification->tipe);
        $this->assertSame('Pengajuan Klaim Baru', $notification->judul);
        $this->assertStringContainsString('2 porsi', $notification->pesan);
        $this->assertFalse($notification->is_read);
        $this->assertDatabaseMissing('notifications', ['user_id' => $recipientUser->id]);

        $this->actingAs($donorUser)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Pengajuan Klaim Baru')
            ->assertSee('data-unread-count="1"', false);
    }

    public function test_ts_pbi8_02_donor_menyetujui_dan_menolak_klaim_membuat_notifikasi_untuk_penerima(): void
    {
        [$recipientUser, $recipientProfile] = $this->recipientWithProfile();
        [$donorUser, $donorProfile] = $this->donorWithProfile();
        $foodPost = $this->foodPostFor($donorProfile);

        $approvedClaim = $this->claimFor($recipientProfile, $foodPost, ['jumlah_porsi' => 2]);
        $rejectedClaim = $this->claimFor($recipientProfile, $foodPost, ['jumlah_porsi' => 1]);

        $this->actingAs($donorUser)
            ->patch(route('donor.claims.approve', $approvedClaim))
            ->assertRedirect();

        $approvedNotification = InAppNotification::query()
            ->where('user_id', $recipientUser->id)
            ->where('judul', 'Klaim Disetujui')
            ->sole();

        $this->assertSame('claim', $approvedNotification->tipe);
        $this->assertStringContainsString($foodPost->judul, $approvedNotification->pesan);

        $this->actingAs($donorUser)
            ->patch(route('donor.claims.reject', $rejectedClaim))
            ->assertRedirect();

        $rejectedNotification = InAppNotification::query()
            ->where('user_id', $recipientUser->id)
            ->where('judul', 'Klaim Ditolak')
            ->sole();

        $this->assertSame('claim', $rejectedNotification->tipe);
        $this->assertStringContainsString($foodPost->judul, $rejectedNotification->pesan);
    }

    public function test_ts_pbi8_04_pendaftaran_terverifikasi_membuat_notifikasi_untuk_pendaftar(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $applicant = $this->activeUser(Role::Donor, 'menunggu');

        VerificationDocument::create([
            'jenis_dokumen' => 'nib',
            'file_path' => 'verification-documents/nib.pdf',
            'status' => VerificationStatus::Menunggu,
            'user_id' => $applicant->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.verifications.approve', $applicant))
            ->assertRedirect(route('admin.verifications.show', $applicant));

        $notification = InAppNotification::query()
            ->where('user_id', $applicant->id)
            ->sole();

        $this->assertSame('akun', $notification->tipe);
        $this->assertSame('Akun Anda Telah Diverifikasi', $notification->judul);
        $this->assertFalse($notification->is_read);

        $this->actingAs($applicant->fresh())
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Akun Anda Telah Diverifikasi')
            ->assertSee('data-unread-count="1"', false);
    }

    public function test_ts_pbi8_05_menandai_satu_notifikasi_mengurangi_angka_lonceng(): void
    {
        $user = $this->activeUser(Role::Donor);
        $first = $this->notificationFor($user, ['judul' => 'Notifikasi pertama']);
        $this->notificationFor($user, ['judul' => 'Notifikasi kedua']);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('data-unread-count="2"', false);

        $this->actingAs($user)
            ->get(route('notifications.open', $first))
            ->assertRedirect(route('notifications.index'))
            ->assertSessionHas('status', 'Notifikasi ditandai sudah dibaca.');

        $this->assertTrue($first->fresh()->is_read);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('data-unread-count="1"', false)
            ->assertSee('1 belum dibaca');
    }

    public function test_ts_pbi8_06_pengguna_tidak_dapat_membuka_notifikasi_milik_pengguna_lain(): void
    {
        $userA = $this->activeUser(Role::Donor);
        $userB = $this->activeUser(Role::Penerima);

        $notificationA = $this->notificationFor($userA, ['judul' => 'Notifikasi milik A']);
        $notificationB = $this->notificationFor($userB, ['judul' => 'Notifikasi milik B']);

        $this->actingAs($userA)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Notifikasi milik A')
            ->assertDontSee('Notifikasi milik B');

        $this->actingAs($userA)
            ->get(route('notifications.open', $notificationB))
            ->assertForbidden();

        $this->assertFalse($notificationB->fresh()->is_read);
        $this->assertFalse($notificationA->fresh()->is_read);
    }

    public function test_ts_pbi8_07_pengguna_baru_tanpa_notifikasi_melihat_empty_state(): void
    {
        $user = $this->activeUser(Role::Penerima);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Belum ada notifikasi')
            ->assertSee('Kembali ke dashboard')
            ->assertSee('data-unread-count="0"', false);
    }

    private function activeUser(Role $role, string $status = 'aktif'): User
    {
        return User::factory()->create([
            'role' => $role,
            'status_akun' => $status,
        ]);
    }

    /**
     * @return array{0: User, 1: DonorProfile}
     */
    private function donorWithProfile(): array
    {
        $user = $this->activeUser(Role::Donor);

        $profile = $user->donorProfile()->create([
            'nama_usaha' => 'Resto Uji '.fake()->unique()->numerify('###'),
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
        $user = $this->activeUser(Role::Penerima);

        $profile = $user->recipientProfile()->create([
            'nama_lembaga' => 'Panti Uji '.fake()->unique()->numerify('###'),
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => 'Jl. Penerima Uji No. 2',
            'lat' => -6.21,
            'lng' => 106.81,
        ]);

        return [$user, $profile];
    }

    private function foodPostFor(DonorProfile $donorProfile): FoodPost
    {
        $category = FoodCategory::create([
            'nama_kategori' => 'Makanan Uji '.fake()->unique()->numerify('###'),
        ]);

        return FoodPost::create([
            'judul' => 'Nasi Box Uji Notifikasi',
            'deskripsi' => 'Makanan untuk pengujian notifikasi.',
            'foto' => 'food-posts/sample.jpg',
            'jumlah_porsi' => 20,
            'sisa_porsi' => 20,
            'alamat' => 'Jl. Donor Uji No. 1',
            'lat' => -6.2,
            'lng' => 106.8,
            'batas_konsumsi' => now()->addHours(8),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donorProfile->id,
            'category_id' => $category->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function claimFor(RecipientProfile $recipientProfile, FoodPost $foodPost, array $attributes = []): Claim
    {
        return Claim::create(array_merge([
            'post_id' => $foodPost->id,
            'recipient_id' => $recipientProfile->id,
            'jumlah_porsi' => 1,
            'status' => 'pending',
            'tanggal_klaim' => today(),
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function notificationFor(User $user, array $attributes = []): InAppNotification
    {
        return InAppNotification::create(array_merge([
            'user_id' => $user->id,
            'judul' => 'Judul notifikasi',
            'pesan' => 'Pesan notifikasi',
            'tipe' => 'claim',
        ], $attributes));
    }
}
