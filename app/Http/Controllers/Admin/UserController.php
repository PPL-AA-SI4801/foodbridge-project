<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUserRequest;
use App\Models\InAppNotification;
use App\Models\User;
use App\Services\Admin\UserManagementService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly UserManagementService $users) {}

    /**
     * Show the administrator's account management list.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'string', 'in:donor,penerima,administrator'],
            'status' => ['nullable', 'string', 'in:menunggu,aktif,ditolak,diblokir'],
        ]);

        $search = trim($validated['q'] ?? '');
        $activeRole = $validated['role'] ?? null;
        $activeStatus = $validated['status'] ?? null;

        $users = $this->users->paginate([
            'q' => $search,
            'role' => $activeRole,
            'status' => $activeStatus,
        ]);

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
            'activeRole' => $activeRole,
            'activeStatus' => $activeStatus,
            'roles' => Role::cases(),
            'statuses' => AccountStatus::cases(),
            'totalUsers' => User::query()->count(),
            'blockedUsers' => User::query()
                ->where('status_akun', AccountStatus::Diblokir->value)
                ->count(),
            'pendingVerifications' => User::query()->pendingVerification()->count(),
        ]);
    }

    /**
     * Show the form for creating a new account.
     */
    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => Role::cases(),
            'pendingVerifications' => User::query()->pendingVerification()->count(),
        ]);
    }

    /**
     * Store a newly created account and its profile.
     */
    public function store(AdminUserRequest $request): RedirectResponse
    {
        $user = $this->users->create($request->validated());

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Akun '.$user->nama.' berhasil dibuat.');
    }

    /**
     * Show the form for editing an account.
     */
    public function edit(User $user): View
    {
        $user->load(['donorProfile', 'recipientProfile']);

        return view('admin.users.edit', [
            'user' => $user,
            'pendingVerifications' => User::query()->pendingVerification()->count(),
        ]);
    }

    /**
     * Update an account and its profile without changing role or status.
     */
    public function update(AdminUserRequest $request, User $user): RedirectResponse
    {
        $this->users->update($user, $request->validated());

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Akun '.$user->nama.' berhasil diperbarui.');
    }

    /**
     * Remove an account when no platform data depends on it.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        try {
            $this->users->delete($user, $request->user());
        } catch (DomainException $exception) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Akun berhasil dihapus.');
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
