<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\FoodRequest;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the administrator area.
     */
    public function index(): View
    {
        FoodRequest::expireOverdue();

        $accountRoles = [Role::Donor->value, Role::Penerima->value];

        return view('admin.dashboard', [
            'pendingVerifications' => User::query()
                ->pendingVerification()
                ->count(),
            'openRequestsCount' => FoodRequest::query()
                ->openBoard()
                ->count(),
            'totalUsersCount' => User::query()
                ->whereIn('role', $accountRoles)
                ->count(),
            'blockedUsersCount' => User::query()
                ->whereIn('role', $accountRoles)
                ->where('status_akun', AccountStatus::Diblokir->value)
                ->count(),
        ]);
    }
}
