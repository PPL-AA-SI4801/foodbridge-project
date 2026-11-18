<?php

namespace App\Http\Controllers;

use App\Enums\VerificationStatus;
use App\Models\VerificationDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class DocumentCompletenessController extends Controller
{
    /**
     * Show the documents an administrator asked the applicant to complete.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        abort_unless($user->mayAccessDocumentCompleteness(), 403);

        $documents = $user->verificationDocuments()
            ->where('status', VerificationStatus::PerluKelengkapan->value)
            ->orderBy('id')
            ->get();

        return view('auth.complete-documents', [
            'user' => $user,
            'documents' => $documents,
        ]);
    }

    /**
     * Replace one document that was marked as needing completeness.
     */
    public function update(Request $request, VerificationDocument $verificationDocument): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->mayAccessDocumentCompleteness(), 403);
        abort_unless($verificationDocument->user_id === $user->id, 403);
        abort_unless($verificationDocument->status === VerificationStatus::PerluKelengkapan, 403);

        $request->validate([
            'dokumen' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ], [
            'dokumen.required' => 'Berkas dokumen wajib diunggah.',
            'dokumen.file' => 'Berkas dokumen tidak valid.',
            'dokumen.mimes' => 'Dokumen harus berupa PDF, JPG, JPEG, atau PNG.',
            'dokumen.max' => 'Ukuran dokumen tidak boleh lebih dari 2 MB.',
        ]);

        $path = $request->file('dokumen')->store(
            "verification-documents/{$user->id}",
            'local',
        );

        if ($path === false) {
            throw new RuntimeException('The replacement document could not be stored.');
        }

        try {
            DB::transaction(function () use ($user, $verificationDocument, $path): void {
                $verificationDocument->update([
                    'status' => VerificationStatus::Ditolak,
                    'verified_at' => null,
                ]);

                $user->verificationDocuments()->create([
                    'jenis_dokumen' => $verificationDocument->jenis_dokumen,
                    'file_path' => $path,
                    'status' => VerificationStatus::Menunggu,
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }

        if (! $user->fresh()->mayAccessDocumentCompleteness()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('status', 'Dokumen perbaikan berhasil diunggah. Akun Anda kembali menunggu verifikasi administrator.');
        }

        return redirect()
            ->route('documents.completeness.edit')
            ->with('status', 'Dokumen berhasil diperbarui. Silakan lengkapi dokumen lain jika ada.');
    }
}
