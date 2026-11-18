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

/**
 * Critical-path tests for PBI-8 in-app notifications.
 *
 * The open destination is derived fresh from the notification type and
 * the account's role and status — no stored URLs.
 */
#[RequiresPhpExtension('pdo_sqlite')]
class NotificationCriticalTest extends TestCase
{
    use RefreshDatabase;

    public function test_klaim_baru_membuka_daftar_klaim_donor_yang_fresh(): void
    {
        [$recipientUser] = $this->recipientWithProfile();
        [$donorUser, $donor] = $this->donorWithProfile();
        $post = $this->foodPostFor($donor);

        $this->actingAs($recipientUser)
            ->post(route('recipient.claims.store', $post), ['jumlah_porsi' => 2])
            ->assertRedirect(route('recipient.claims.index'));

        $notification = InAppNotification::query()
            ->where('user_id', $donorUser->id)
            ->sole();

        $this->assertSame('klaim', $notification->tipe);

        $this->actingAs($donorUser)
            ->get(route('notifications.open', $notification))
            ->assertRedirect(route('donor.claims.index'));

        $this->assertTrue($notification->fresh()->is_read);
    }

    public function test_klaim_disetujui_dan_ditolak_membuka_daftar_klaim_penerima_yang_fresh(): void
    {
        [$recipientUser, $recipient] = $this->recipientWithProfile();
        [$donorUser, $donor] = $this->donorWithProfile();
        $approved = $this->claimFor($recipient, $this->foodPostFor($donor));
        $rejected = $this->claimFor($recipient, $this->foodPostFor($donor));

        $this->actingAs($donorUser)
            ->patch(route('donor.claims.approve', $approved))
            ->assertRedirect();

        $this->actingAs($donorUser)
            ->patch(route('donor.claims.reject', $rejected))
            ->assertRedirect();

        $approvedNotification = InAppNotification::query()
            ->where('user_id', $recipientUser->id)
            ->where('judul', 'Klaim Disetujui')
            ->sole();
        $rejectedNotification = InAppNotification::query()
            ->where('user_id', $recipientUser->id)
            ->where('judul', 'Klaim Ditolak')
            ->sole();

        $this->assertStringContainsString('disetujui', $approvedNotification->pesan);
        $this->assertStringContainsString('ditolak', $rejectedNotification->pesan);

        $this->actingAs($recipientUser)
            ->get(route('notifications.open', $approvedNotification))
            ->assertRedirect(route('recipient.claims.index'));

        $this->actingAs($recipientUser)
            ->get(route('notifications.open', $rejectedNotification))
            ->assertRedirect(route('recipient.claims.index'));
    }

    public function test_permintaan_kelengkapan_dokumen_sampai_ke_pendaftar(): void
    {
        $admin = $this->user(Role::Administrator);
        $applicant = $this->user(Role::Donor, 'menunggu');

        VerificationDocument::create([
            'jenis_dokumen' => 'nib',
            'file_path' => 'verification-documents/nib.pdf',
            'status' => VerificationStatus::Menunggu,
            'user_id' => $applicant->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.verifications.request-documents', $applicant), [
                'completeness_catatan' => 'NIB kurang jelas, mohon unggah ulang.',
            ])
            ->assertRedirect();

        $notification = InAppNotification::query()
            ->where('user_id', $applicant->id)
            ->sole();

        $this->assertSame('Dokumen Akun Perlu Dilengkapi', $notification->judul);
        $this->assertStringContainsString('NIB kurang jelas', $notification->pesan);

        $this->actingAs($applicant->fresh())
            ->get(route('notifications.open', $notification))
            ->assertRedirect(route('documents.completeness.edit'));
    }

    public function test_akun_disetujui_membuka_dashboard(): void
    {
        $admin = $this->user(Role::Administrator);
        $applicant = $this->user(Role::Donor, 'menunggu');

        VerificationDocument::create([
            'jenis_dokumen' => 'nib',
            'file_path' => 'verification-documents/nib.pdf',
            'status' => VerificationStatus::Menunggu,
            'user_id' => $applicant->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.verifications.approve', $applicant))
            ->assertRedirect();

        $notification = InAppNotification::query()
            ->where('user_id', $applicant->id)
            ->sole();

        $this->actingAs($applicant->fresh())
            ->get(route('notifications.open', $notification))
            ->assertRedirect(route('dashboard.donor'));
    }

    public function test_notifikasi_milik_orang_lain_tidak_bisa_dibuka(): void
    {
        $userA = $this->user(Role::Donor);
        $userB = $this->user(Role::Penerima);

        $notificationB = InAppNotification::create([
            'user_id' => $userB->id,
            'judul' => 'Rahasia',
            'pesan' => 'Bukan milik A.',
            'tipe' => 'klaim',
        ]);

        $this->actingAs($userA)
            ->get(route('notifications.open', $notificationB))
            ->assertForbidden();

        $this->assertFalse($notificationB->fresh()->is_read);
    }

    public function test_tipe_tidak_dikenal_kembali_ke_daftar_notifikasi(): void
    {
        $user = $this->user(Role::Donor);

        $notification = InAppNotification::create([
            'user_id' => $user->id,
            'judul' => 'Tidak dikenal',
            'pesan' => 'Tipe ini tidak punya halaman tujuan.',
            'tipe' => 'tipe-aneh',
        ]);

        $this->actingAs($user)
            ->get(route('notifications.open', $notification))
            ->assertRedirect(route('notifications.index'));

        $this->assertTrue($notification->fresh()->is_read);
    }

    private function user(Role $role, string $status = 'aktif'): User
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
        $user = $this->user(Role::Donor);

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
        $user = $this->user(Role::Penerima);

        $profile = $user->recipientProfile()->create([
            'nama_lembaga' => 'Panti Uji '.fake()->unique()->numerify('###'),
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => 'Jl. Penerima Uji No. 2',
            'lat' => -6.21,
            'lng' => 106.81,
        ]);

        return [$user, $profile];
    }

    private function foodPostFor(DonorProfile $donor): FoodPost
    {
        $category = FoodCategory::create([
            'nama_kategori' => 'Kategori '.fake()->unique()->numerify('###'),
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
            'status' => 'pending',
            'tanggal_klaim' => today(),
        ]);
    }
}
