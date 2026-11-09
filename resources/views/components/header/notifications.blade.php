{{-- Notifikasi in-app (FR-10) untuk header. --}}
@props(['limit' => 5])

@php
$notifications = \App\Models\InAppNotification::query()
    ->where('user_id', auth()->id())
    ->latest()
    ->limit($limit)
    ->get();

$unreadCount = \App\Models\InAppNotification::query()
    ->where('user_id', auth()->id())
    ->where('is_read', false)
    ->count();
@endphp

<div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false" dusk="notification-bell" data-unread-count="{{ $unreadCount }}">
    <button
        type="button"
        @click="open = ! open"
        x-bind:aria-expanded="open"
        aria-label="Notifikasi"
        aria-haspopup="true"
        aria-controls="notification-menu-dropdown"
        class="relative inline-flex h-9 w-9 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
    >
        <x-icon name="bell" class="h-5 w-5" />

        @if ($unreadCount > 0)
            <span class="absolute -right-0.5 -top-0.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-secondary px-1 text-[10px] font-medium text-white">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div id="notification-menu-dropdown" x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 z-50 mt-2">
        <div class="w-80 overflow-hidden rounded-md border border-border bg-popover text-popover-foreground">
            <div class="flex items-center justify-between border-b border-border px-4 py-2.5">
                <span class="text-sm font-medium">Notifikasi</span>

                @if ($unreadCount > 0)
                    <span class="inline-flex items-center gap-1.5 text-xs text-muted-foreground">
                        <span class="h-1.5 w-1.5 rounded-full bg-secondary" aria-hidden="true"></span>
                        {{ $unreadCount }} belum dibaca
                    </span>
                @endif
            </div>

            @if ($notifications->isEmpty())
                <p class="px-4 py-6 text-center text-sm text-muted-foreground">Belum ada notifikasi.</p>
            @else
                <div class="flex flex-col p-1">
                    @foreach ($notifications as $notification)
                        @php
                            $meta = \App\Support\NotificationPresenter::for($notification->tipe);
                        @endphp

                        <div @class([
                            'flex gap-3 rounded-sm px-3 py-2.5 transition-colors hover:bg-accent',
                            'bg-accent/50' => ! $notification->is_read,
                        ])>
                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md {{ $meta['iconClass'] }}">
                                <x-icon :name="$meta['icon']" class="h-4 w-4" />
                            </span>

                            <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                                <div class="flex items-start justify-between gap-2">
                                    <a
                                        href="{{ route('notifications.open', $notification) }}"
                                        title="Buka notifikasi"
                                        class="min-w-0 flex-1 truncate text-sm font-medium transition-colors hover:text-primary hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    >
                                        {{ $notification->judul }}
                                    </a>

                                    @if (! $notification->is_read)
                                        <form method="POST" action="{{ route('notifications.read', $notification) }}" class="shrink-0">
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                dusk="header-mark-read-{{ $notification->id }}"
                                                title="Tandai sudah dibaca"
                                                aria-label="Tandai {{ $notification->judul }} sebagai sudah dibaca"
                                                class="inline-flex h-6 w-6 items-center justify-center rounded text-muted-foreground transition-colors hover:bg-card hover:text-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                            >
                                                <x-icon name="check" class="h-3.5 w-3.5" />
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                <span class="line-clamp-2 text-xs leading-5 text-muted-foreground">{{ $notification->pesan }}</span>
                                <span class="mt-0.5 text-xs text-muted-foreground">{{ $notification->created_at->locale('id')->diffForHumans() }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if (\Illuminate\Support\Facades\Route::has('notifications.index'))
                <div class="border-t border-border p-1">
                    @if ($unreadCount > 0 && \Illuminate\Support\Facades\Route::has('notifications.read-all'))
                        <form method="POST" action="{{ route('notifications.read-all') }}">
                            @csrf
                            @method('PATCH')

                            <button
                                type="submit"
                                dusk="header-mark-all-read"
                                class="flex w-full items-center gap-2 rounded-sm px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            >
                                <x-icon name="check" class="h-4 w-4" />
                                Tandai semua dibaca
                            </button>
                        </form>
                    @endif

                    <a
                        href="{{ route('notifications.index') }}"
                        class="flex items-center justify-between gap-3 rounded-sm px-3 py-2 text-sm font-medium text-primary transition-colors hover:bg-accent focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    >
                        <span class="inline-flex items-center gap-2">
                            <x-icon name="bell" class="h-4 w-4" />
                            Lihat semua notifikasi
                        </span>

                        @if ($unreadCount > 0)
                            <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-secondary/10 px-1.5 text-[10px] font-semibold text-secondary">
                                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                            </span>
                        @else
                            <x-icon name="chevron-right" class="h-4 w-4" />
                        @endif
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
