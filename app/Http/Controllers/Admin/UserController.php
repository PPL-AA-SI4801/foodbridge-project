<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\InAppNotification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    /**
     * Block an active Donor or Penerima account.
     */
    public function block(User $user): RedirectResponse
    {
        $result = DB::transaction(function () use ($user): string {
            $user->refresh();

            if (! $user->hasRole(Role::Donor, Role::Penerima)) {
                return 'not_blockable';
            }

            if ($user->status_akun === AccountStatus::Diblokir) {
                return 'already_blocked';
            }

            if ($user->status_akun !== AccountStatus::Aktif) {
                return 'not_active';
            }

            $user->update(['status_akun' => AccountStatus::Diblokir]);

            InAppNotification::create([
                'user_id' => $user->id,
                'judul' => 'Akun Anda Diblokir',
                'pesan' => 'Akun Anda telah diblokir oleh administrator. Anda tidak dapat mengakses FoodBridge.',
                'tipe' => 'akun',
            ]);

            // End any browser session that belongs to the blocked account immediately.
            DB::table('sessions')->where('user_id', $user->id)->delete();

            return 'blocked';
        });

        if ($result === 'not_blockable') {
            abort(404);
        }

        if ($result === 'already_blocked') {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Akun ini sudah diblokir.');
        }

        if ($result === 'not_active') {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Hanya akun aktif yang dapat diblokir.');
        }

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Akun berhasil diblokir.');
    }
}
