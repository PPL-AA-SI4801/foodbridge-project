<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Show the profile edit form for the authenticated Donor or Recipient.
     */
    public function edit(): View
    {
        $user = Auth::user();
        $profile = $this->profileFor($user);

        return view('profile.edit', [
            'user' => $user,
            'profile' => $profile,
        ]);
    }

    /**
     * Update the authenticated account and its organization profile.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $validated = $request->validated();

        DB::transaction(function () use ($user, $validated): void {
            $user->update([
                'nama' => $validated['nama'],
                'email' => $validated['email'],
                'no_hp' => $validated['no_hp'],
            ]);

            if (! empty($validated['password'])) {
                $user->update(['password' => $validated['password']]);
            }

            $profile = $this->profileFor($user);

            if ($user->role === Role::Donor) {
                $profile->update([
                    'nama_usaha' => $validated['nama_usaha'],
                    'jenis_usaha' => $validated['jenis_usaha'],
                    'alamat' => $validated['alamat'],
                    'lat' => $validated['lat'],
                    'lng' => $validated['lng'],
                ]);
            } else {
                $profile->update([
                    'nama_lembaga' => $validated['nama_lembaga'],
                    'jenis_lembaga' => $validated['jenis_lembaga'],
                    'alamat' => $validated['alamat'],
                    'lat' => $validated['lat'],
                    'lng' => $validated['lng'],
                ]);
            }
        });

        return redirect()
            ->route('profile.edit')
            ->with('status', 'Profil berhasil diperbarui.');
    }

    /**
     * Resolve (or create) the organization profile for the given account.
     */
    private function profileFor(User $user)
    {
        return match ($user->role) {
            Role::Donor => $user->donorProfile()->firstOrCreate(['user_id' => $user->id]),
            Role::Penerima => $user->recipientProfile()->firstOrCreate(['user_id' => $user->id]),
            default => abort(403),
        };
    }
}