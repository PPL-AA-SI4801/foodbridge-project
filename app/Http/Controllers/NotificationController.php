<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\InAppNotification;
use App\Models\User;
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
}
