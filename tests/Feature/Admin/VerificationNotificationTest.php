<?php

namespace Tests\Feature\Admin;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Models\InAppNotification;
use App\Models\User;
use App\Models\VerificationDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class VerificationNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_a_registration_creates_an_account_notification(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $applicant = $this->pendingApplicant();

        $this->actingAs($admin)
            ->post(route('admin.verifications.approve', $applicant))
            ->assertRedirect(route('admin.verifications.show', $applicant));

        $notification = InAppNotification::query()
            ->where('user_id', $applicant->id)
            ->sole();

        $this->assertSame('akun', $notification->tipe);
        $this->assertSame('Akun Anda Telah Diverifikasi', $notification->judul);
        $this->assertStringContainsString('telah disetujui', $notification->pesan);
        $this->assertFalse($notification->is_read);
        $this->assertSame(AccountStatus::Aktif, $applicant->fresh()->status_akun);
    }

    public function test_rejecting_a_registration_creates_an_account_notification_with_the_reason(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $applicant = $this->pendingApplicant();

        $this->actingAs($admin)
            ->post(route('admin.verifications.reject', $applicant), [
                'catatan' => 'Dokumen legalitas tidak terbaca dengan jelas.',
            ])
            ->assertRedirect(route('admin.verifications.show', $applicant));

        $notification = InAppNotification::query()
            ->where('user_id', $applicant->id)
            ->sole();

        $this->assertSame('akun', $notification->tipe);
        $this->assertSame('Pendaftaran Akun Ditolak', $notification->judul);
        $this->assertStringContainsString(
            'Dokumen legalitas tidak terbaca dengan jelas.',
            $notification->pesan,
        );
        $this->assertFalse($notification->is_read);
        $this->assertSame(AccountStatus::Ditolak, $applicant->fresh()->status_akun);
    }

    private function activeUser(Role $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'status_akun' => 'aktif',
        ]);
    }

    private function pendingApplicant(): User
    {
        $applicant = User::factory()->create([
            'role' => Role::Donor,
            'status_akun' => 'menunggu',
        ]);

        VerificationDocument::create([
            'jenis_dokumen' => 'nib',
            'file_path' => 'verification-documents/nib.pdf',
            'status' => VerificationStatus::Menunggu,
            'user_id' => $applicant->id,
        ]);

        return $applicant;
    }
}
