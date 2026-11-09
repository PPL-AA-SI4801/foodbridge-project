<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\InAppNotification;
use App\Models\User;
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
     */
    public function open(Request $request, InAppNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->markAsRead();

        return redirect()
            ->route('notifications.index')
            ->with('status', 'Notifikasi ditandai sudah dibaca.');
    }

    /**
     * Mark one of the authenticated account's notifications as read.
     */
    public function markAsRead(Request $request, InAppNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->markAsRead();

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
}
