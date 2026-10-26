<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $role = Role::from($validated['role']);
        $path = null;

        try {
            DB::transaction(function () use ($request, $validated, $role, &$path): void {
                $user = User::create([
                    'nama' => $validated['nama'],
                    'email' => $validated['email'],
                    'password' => $validated['password'],
                    'no_hp' => $validated['no_hp'],
                    'role' => $role,
                    'status_akun' => AccountStatus::Menunggu,
                ]);

                if ($role === Role::Donor) {
                    $user->donorProfile()->create([
                        'nama_usaha' => $validated['nama_usaha'],
                        'jenis_usaha' => $validated['jenis_usaha'],
                        'alamat' => $validated['alamat'],
                    ]);
                } else {
                    $user->recipientProfile()->create([
                        'nama_lembaga' => $validated['nama_lembaga'],
                        'jenis_lembaga' => $validated['jenis_lembaga'],
                        'alamat' => $validated['alamat'],
                    ]);
                }

                $storedPath = $request->file('dokumen')->store(
                    "verification-documents/{$user->id}",
                    'local',
                );

                if ($storedPath === false) {
                    throw new RuntimeException('The legality document could not be stored.');
                }

                $path = $storedPath;

                $user->verificationDocuments()->create([
                    'jenis_dokumen' => $validated['jenis_dokumen'],
                    'file_path' => $path,
                    'status' => VerificationStatus::Menunggu,
                ]);
            });
        } catch (Throwable $exception) {
            if ($path !== null) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }

        return redirect()
            ->route('login')
            ->with('status', 'Pendaftaran berhasil. Akun Anda sedang menunggu verifikasi administrator.');
    }
}
