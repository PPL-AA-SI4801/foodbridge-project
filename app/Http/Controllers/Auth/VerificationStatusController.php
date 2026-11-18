<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Models\VerificationDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class VerificationStatusController extends Controller
{
    /**
     * Return the verification state of the account tracked in session.
     */
    public function status(Request $request): JsonResponse
    {
        $email = $request->session()->get('pending_verification_email');
        $user = is_string($email) && $email !== ''
            ? User::where('email', $email)->first()
            : null;

        if ($user === null) {
            return response()->json(['tracked' => false]);
        }

        return response()->json([
            'tracked' => true,
            'status_akun' => $user->status_akun->value,
            'needs_completeness' => $user->hasDocumentCompletenessRequest(),
            'is_reverification' => $user->verificationDocuments()
                ->where('status', VerificationStatus::Ditolak->value)
                ->exists(),
        ]);
    }

    /**
     * Sign in the tracked account once it has become active, then send it
     * to its role dashboard. The session key is single-use: it is forgotten
     * immediately so this URL cannot be replayed.
     */
    public function enter(Request $request): RedirectResponse
    {
        $email = $request->session()->get('pending_verification_email');
        $user = is_string($email) && $email !== ''
            ? User::where('email', $email)->first()
            : null;

        if (
            $user === null
            || $user->status_akun !== AccountStatus::Aktif
            || ! $user->hasRole(Role::Donor, Role::Penerima)
        ) {
            return redirect()->route('login');
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('pending_verification_email');

        return redirect()->intended(route($user->dashboardRouteName(), absolute: false));
    }

    /**
     * Accept a completeness document from the pending account tracked in session.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request);
        abort_unless($user !== null, 403);

        $validated = $request->validate([
            'jenis_dokumen' => ['required', 'string', Rule::in(RegisterRequest::DOCUMENT_TYPES)],
            'dokumen' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ], [
            'jenis_dokumen.required' => 'Jenis dokumen wajib dipilih.',
            'jenis_dokumen.in' => 'Jenis dokumen tidak valid.',
            'dokumen.required' => 'Berkas dokumen wajib diunggah.',
            'dokumen.file' => 'Berkas dokumen tidak valid.',
            'dokumen.mimes' => 'Dokumen harus berupa PDF, JPG, JPEG, atau PNG.',
            'dokumen.max' => 'Ukuran dokumen tidak boleh lebih dari 2 MB.',
        ]);

        $document = $user->verificationDocuments()
            ->where('jenis_dokumen', $validated['jenis_dokumen'])
            ->where('status', VerificationStatus::PerluKelengkapan->value)
            ->latest('id')
            ->first();

        if ($document === null) {
            return redirect()->route('login')->withErrors([
                'dokumen' => 'Tidak ada dokumen '.$validated['jenis_dokumen'].' yang perlu dilengkapi.',
            ]);
        }

        abort_unless($document->user_id === $user->id, 403);

        $path = $request->file('dokumen')->store(
            "verification-documents/{$user->id}",
            'local',
        );

        if ($path === false) {
            throw new RuntimeException('The replacement document could not be stored.');
        }

        try {
            DB::transaction(function () use ($user, $document, $path): void {
                $document->update([
                    'status' => VerificationStatus::Ditolak->value,
                    'verified_at' => null,
                ]);

                $user->verificationDocuments()->create([
                    'jenis_dokumen' => $document->jenis_dokumen,
                    'file_path' => $path,
                    'status' => VerificationStatus::Menunggu->value,
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }

        return redirect()
            ->route('login')
            ->with('status', 'Dokumen perbaikan berhasil diunggah. Akun Anda kembali menunggu verifikasi administrator.');
    }

    /**
     * Resolve the pending account tracked in session, if it is still waiting.
     */
    private function pendingUser(Request $request): ?User
    {
        $email = $request->session()->get('pending_verification_email');

        if (! is_string($email) || $email === '') {
            return null;
        }

        $user = User::where('email', $email)->first();

        if ($user === null || $user->status_akun !== AccountStatus::Menunggu) {
            return null;
        }

        return $user;
    }
}
