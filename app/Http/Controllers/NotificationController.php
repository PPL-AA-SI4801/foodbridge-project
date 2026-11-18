<?php

namespace App\Http\Controllers;

use App\Enums\AccountStatus;
use App\Enums\NotificationType;
use App\Enums\Role;
use App\Models\InAppNotification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Show the authenticated account's in-app notifications.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';

        $baseQuery = InAppNotification::query()
            ->where('user_id', $user->id);

        $totalCount = (clone $baseQuery)->count();
        $unreadCount = (clone $baseQuery)->where('is_read', false)->count();

        $notifications = $baseQuery
            ->when($filter === 'unread', fn ($query) => $query->where('is_read', false))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $isAdmin = $user->hasRole(Role::Administrator);

        return view('notifications.index', [
            'notifications' => $notifications,
            'filter' => $filter,
            'totalCount' => $totalCount,
            'unreadCount' => $unreadCount,
            'isAdmin' => $isAdmin,
            'pendingVerifications' => $isAdmin
                ? User::query()->pendingVerification()->count()
                : 0,
        ]);
    }

    /**
     * Open one of the authenticated account's notifications and mark it as read.
     *
     * The destination is derived fresh from the notification type and the
     * account's role and status, so it always reflects current data.
     */
    public function open(Request $request, InAppNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->markAsRead();

        return redirect()->to($this->targetFor($request->user(), $notification->tipe));
    }

    /**
     * Mark one of the authenticated account's notifications as read.
     */
    public function markAsRead(Request $request, InAppNotification $notification): RedirectResponse|JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->markAsRead();

        if ($request->expectsJson()) {
            return response()->json([
                'is_read' => true,
                'unread_count' => $request->user()
                    ->inAppNotifications()
                    ->unread()
                    ->count(),
            ]);
        }

        return back()->with('status', 'Notifikasi ditandai sudah dibaca.');
    }

    /**
     * Mark every unread notification of the authenticated account as read.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()
            ->inAppNotifications()
            ->unread()
            ->update(['is_read' => true]);

        return back()->with('status', 'Semua notifikasi ditandai sudah dibaca.');
    }

    /**
     * Derive the page a notification should open from its type and the
     * account's current role and status. Always a relative in-app path.
     */
    private function targetFor(User $user, ?string $tipe): string
    {
        $type = NotificationType::tryFrom((string) $tipe);

        if ($user->hasRole(Role::Administrator)) {
            return match ($type) {
                NotificationType::Account => route('admin.verifications.index', [], false),
                default => route('notifications.index', [], false),
            };
        }

        $isDonor = $user->hasRole(Role::Donor);

        return match ($type) {
            NotificationType::Claim => $isDonor
                ? route('donor.claims.index', [], false)
                : route('recipient.claims.index', [], false),
            NotificationType::FoodRequest => $isDonor
                ? route('donor.requests.index', [], false)
                : route('recipient.requests.index', [], false),
            NotificationType::FoodPost => route('donor.food-posts.index', [], false),
            NotificationType::Account => $user->status_akun === AccountStatus::Menunggu
                ? route('documents.completeness.edit', [], false)
                : ($isDonor
                    ? route('dashboard.donor', [], false)
                    : route('dashboard.recipient', [], false)),
            default => route('notifications.index', [], false),
        };
    }
}
