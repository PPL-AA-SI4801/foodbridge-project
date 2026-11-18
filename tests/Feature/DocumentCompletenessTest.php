<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Models\InAppNotification;
use App\Models\User;
use App\Models\VerificationDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class DocumentCompletenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_request_document_completeness(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $applicant = $this->pendingApplicant();
        $document = $this->documentFor($applicant);

        $this->actingAs($admin)
            ->post(route('admin.verifications.request-documents', $applicant), [
                'completeness_catatan' => 'NIB kurang jelas, mohon unggah ulang.',
            ])
            ->assertRedirect(route('admin.verifications.show', $applicant))
            ->assertSessionHas('status', 'Permintaan kelengkapan dokumen berhasil dikirim.');

        $this->assertSame(AccountStatus::Menunggu, $applicant->fresh()->status_akun);

        $document->refresh();

        $this->assertSame(VerificationStatus::PerluKelengkapan, $document->status);
        $this->assertSame('NIB kurang jelas, mohon unggah ulang.', $document->catatan);
        $this->assertNull($document->verified_at);

        $notification = InAppNotification::query()
            ->where('user_id', $applicant->id)
            ->sole();

        $this->assertSame('akun', $notification->tipe);
        $this->assertSame('Dokumen Akun Perlu Dilengkapi', $notification->judul);
        $this->assertStringContainsString('NIB kurang jelas, mohon unggah ulang.', $notification->pesan);
        $this->assertFalse($notification->is_read);
    }

    public function test_pending_applicant_can_login_to_complete_requested_documents(): void
    {
        $applicant = $this->pendingApplicant();
        $this->documentFor($applicant, [
            'status' => VerificationStatus::PerluKelengkapan,
            'catatan' => 'Dokumen perlu diganti.',
        ]);

        $this->post(route('login'), [
            'email' => $applicant->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard.donor'));

        $this->assertAuthenticatedAs($applicant);

        $this->get(route('documents.completeness.edit'))
            ->assertOk()
            ->assertSee('Lengkapi Dokumen')
            ->assertSee('NIB')
            ->assertSee('Dokumen perlu diganti.');
    }

    public function test_pending_applicant_without_completeness_request_is_logged_out(): void
    {
        $applicant = $this->pendingApplicant();
        $this->documentFor($applicant);

        $this->actingAs($applicant)
            ->get(route('documents.completeness.edit'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_applicant_uploads_a_replacement_and_returns_to_waiting_for_verification(): void
    {
        Storage::fake('local');

        $applicant = $this->pendingApplicant();
        $document = $this->documentFor($applicant, [
            'status' => VerificationStatus::PerluKelengkapan,
            'catatan' => 'Dokumen tidak lengkap.',
        ]);

        $this->actingAs($applicant)
            ->post(route('documents.completeness.update', $document), [
                'dokumen' => UploadedFile::fake()->create('nib-baru.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Dokumen perbaikan berhasil diunggah. Akun Anda kembali menunggu verifikasi administrator.');

        $this->assertGuest();
        $this->assertSame(VerificationStatus::Ditolak, $document->fresh()->status);
        $this->assertSame('Dokumen tidak lengkap.', $document->fresh()->catatan);
        $this->assertSame(AccountStatus::Menunggu, $applicant->fresh()->status_akun);

        $replacement = $applicant->verificationDocuments()
            ->where('status', VerificationStatus::Menunggu->value)
            ->sole();

        $this->assertSame('nib', $replacement->jenis_dokumen);
        $this->assertTrue(Storage::disk('local')->exists($replacement->file_path));
    }

    public function test_administrator_cannot_approve_while_completeness_is_pending(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $applicant = $this->pendingApplicant();
        $this->documentFor($applicant, ['status' => VerificationStatus::PerluKelengkapan]);

        $this->actingAs($admin)
            ->post(route('admin.verifications.approve', $applicant))
            ->assertRedirect(route('admin.verifications.show', $applicant))
            ->assertSessionHas('error', 'Pendaftar masih memiliki dokumen yang ditolak atau perlu dilengkapi sebelum akun dapat disetujui.');

        $this->assertSame(AccountStatus::Menunggu, $applicant->fresh()->status_akun);
    }

    public function test_administrator_cannot_approve_when_the_latest_document_is_rejected(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $applicant = $this->pendingApplicant();
        $this->documentFor($applicant, ['status' => VerificationStatus::Ditolak]);

        $this->actingAs($admin)
            ->post(route('admin.verifications.approve', $applicant))
            ->assertRedirect(route('admin.verifications.show', $applicant))
            ->assertSessionHas('error', 'Pendaftar masih memiliki dokumen yang ditolak atau perlu dilengkapi sebelum akun dapat disetujui.');

        $this->assertSame(AccountStatus::Menunggu, $applicant->fresh()->status_akun);
    }

    public function test_requesting_completeness_marks_the_latest_rejected_document_as_incomplete(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $applicant = $this->pendingApplicant();
        $document = $this->documentFor($applicant, ['status' => VerificationStatus::Ditolak]);

        $this->actingAs($admin)
            ->post(route('admin.verifications.request-documents', $applicant), [
                'completeness_catatan' => 'Dokumen ditolak, mohon unggah ulang.',
            ])
            ->assertRedirect(route('admin.verifications.show', $applicant))
            ->assertSessionHas('status', 'Permintaan kelengkapan dokumen berhasil dikirim.');

        $this->assertSame(VerificationStatus::PerluKelengkapan, $document->fresh()->status);
    }

    public function test_administrator_can_approve_after_the_replacement_is_uploaded(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $applicant = $this->pendingApplicant();
        $this->documentFor($applicant, ['status' => VerificationStatus::Ditolak]);
        $this->documentFor($applicant, ['status' => VerificationStatus::Menunggu]);

        $this->actingAs($admin)
            ->post(route('admin.verifications.approve', $applicant))
            ->assertRedirect(route('admin.verifications.show', $applicant))
            ->assertSessionHas('status', 'Pendaftaran berhasil disetujui. Akun sekarang aktif.');

        $this->assertSame(AccountStatus::Aktif, $applicant->fresh()->status_akun);
        $this->assertSame(
            1,
            $applicant->verificationDocuments()
                ->where('status', VerificationStatus::Disetujui->value)
                ->count(),
        );
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
        return User::factory()->create([
            'role' => Role::Donor,
            'status_akun' => 'menunggu',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function documentFor(User $user, array $attributes = []): VerificationDocument
    {
        return VerificationDocument::create(array_merge([
            'user_id' => $user->id,
            'jenis_dokumen' => 'nib',
            'file_path' => 'verification-documents/'.$user->id.'/nib.pdf',
            'status' => VerificationStatus::Menunggu,
        ], $attributes));
    }
}
