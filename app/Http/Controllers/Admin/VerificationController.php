<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectVerificationRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VerificationController extends Controller
{
    /**
     * Show the queue of accounts awaiting verification.
     */
    public function index(): View
    {
        $applicants = User::query()
            ->pendingVerification()
            ->with([
                'donorProfile',
                'recipientProfile',
                'verificationDocuments' => fn ($query) => $query->orderByDesc('id'),
            ])
            ->withMax('verificationDocuments as latest_document_id', 'id')
            ->orderByDesc('latest_document_id')
            ->get();

        return view('admin.verifications.index', [
            'applicants' => $applicants,
            'pendingVerifications' => $applicants->count(),
        ]);
    }

    /**
     * Show one registration for review.
     */
    public function show(User $user): View
    {
        $this->ensureVerificationAccount($user);

        $user->load([
            'donorProfile',
            'recipientProfile',
            'verificationDocuments' => fn ($query) => $query->orderByDesc('id'),
        ]);

        return view('admin.verifications.show', [
            'user' => $user,
            'pendingVerifications' => User::query()->pendingVerification()->count(),
        ]);
    }

    /**
     * Approve a pending registration and activate the account.
     */
    public function approve(User $user): RedirectResponse
    {
        $result = DB::transaction(function () use ($user): string {
            $user->refresh();

            if (! $user->hasRole(Role::Donor, Role::Penerima)) {
                return 'not_verifiable';
            }

            if ($user->status_akun !== AccountStatus::Menunggu) {
                return 'already_processed';
            }

            if (! $user->verificationDocuments()->exists()) {
                return 'document_required';
            }

            $reviewedAt = now();

            $user->update(['status_akun' => AccountStatus::Aktif]);

            $user->verificationDocuments()
                ->where('status', VerificationStatus::Menunggu->value)
                ->update([
                    'status' => VerificationStatus::Disetujui->value,
                    'catatan' => null,
                    'verified_at' => $reviewedAt,
                ]);

            return 'approved';
        });

        if ($result === 'not_verifiable') {
            abort(404);
        }

        if ($result === 'already_processed') {
            return redirect()
                ->route('admin.verifications.show', $user)
                ->with('error', 'Pendaftaran ini sudah diproses.');
        }

        if ($result === 'document_required') {
            return redirect()
                ->route('admin.verifications.show', $user)
                ->with('error', 'Dokumen legalitas belum diunggah, sehingga akun tidak dapat disetujui.');
        }

        return redirect()
            ->route('admin.verifications.show', $user)
            ->with('status', 'Pendaftaran berhasil disetujui. Akun sekarang aktif.');
    }

    /**
     * Reject a pending registration with a required reason.
     */
    public function reject(RejectVerificationRequest $request, User $user): RedirectResponse
    {
        $catatan = $request->validated('catatan');

        $result = DB::transaction(function () use ($user, $catatan): string {
            $user->refresh();

            if (! $user->hasRole(Role::Donor, Role::Penerima)) {
                return 'not_verifiable';
            }

            if ($user->status_akun !== AccountStatus::Menunggu) {
                return 'already_processed';
            }

            if (! $user->verificationDocuments()->exists()) {
                return 'document_required';
            }

            $reviewedAt = now();

            $user->update(['status_akun' => AccountStatus::Ditolak]);

            $user->verificationDocuments()
                ->where('status', VerificationStatus::Menunggu->value)
                ->update([
                    'status' => VerificationStatus::Ditolak->value,
                    'catatan' => $catatan,
                    'verified_at' => $reviewedAt,
                ]);

            return 'rejected';
        });

        if ($result === 'not_verifiable') {
            abort(404);
        }

        if ($result === 'already_processed') {
            return redirect()
                ->route('admin.verifications.show', $user)
                ->with('error', 'Pendaftaran ini sudah diproses.');
        }

        if ($result === 'document_required') {
            return redirect()
                ->route('admin.verifications.show', $user)
                ->with('error', 'Dokumen legalitas belum diunggah, sehingga akun tidak dapat ditolak.');
        }

        return redirect()
            ->route('admin.verifications.show', $user)
            ->with('status', 'Pendaftaran berhasil ditolak.');
    }

    /**
     * Ensure the bound account is a Donor or Penerima, never an administrator.
     */
    private function ensureVerificationAccount(User $user): void
    {
        abort_unless($user->hasRole(Role::Donor, Role::Penerima), 404);
    }
}
