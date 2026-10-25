<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the administrator area.
     */
    public function index(): View
    {
        return view('admin.dashboard', [
            'pendingVerifications' => User::query()
                ->where('status_akun', AccountStatus::Menunggu->value)
                ->count(),
        ]);
    }
}
