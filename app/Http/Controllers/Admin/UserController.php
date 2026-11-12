<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Show the administrator's account management list.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $status = $request->query('status');
        $status = in_array($status, array_column(AccountStatus::cases(), 'value'), true)
            ? $status
            : null;

        $accountRoles = [Role::Donor->value, Role::Penerima->value];

        $users = User::query()
            ->whereIn('role', $accountRoles)
            ->with(['donorProfile', 'recipientProfile'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('nama', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('no_hp', 'like', "%{$search}%");
                });
            })
            ->when($status !== null, fn ($query) => $query->where('status_akun', $status))
            ->orderBy('nama')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
            'status' => $status,
            'statuses' => AccountStatus::cases(),
            'totalUsers' => User::query()->whereIn('role', $accountRoles)->count(),
            'blockedUsers' => User::query()
                ->whereIn('role', $accountRoles)
                ->where('status_akun', AccountStatus::Diblokir->value)
                ->count(),
            'pendingVerifications' => User::query()->pendingVerification()->count(),
        ]);
    }
}
