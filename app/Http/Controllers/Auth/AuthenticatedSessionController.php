<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        $pendingVerificationUser = null;
        $email = session('pending_verification_email');

        if (is_string($email) && $email !== '') {
            $user = User::where('email', $email)->first();

            if ($user !== null && $user->status_akun === AccountStatus::Menunggu) {
                $pendingVerificationUser = $user;
            }
        }

        return view('auth.login', ['pendingVerificationUser' => $pendingVerificationUser]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        $request->session()->forget('pending_verification_email');

        $redirect = $request->user()->dashboardRouteName();

        return redirect()->intended(route($redirect, absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
