{{-- Notifikasi in-app (FR-10) untuk header. --}}
@props(['limit' => 5])

@php
$notifications = once(fn () => \App\Models\InAppNotification::query()
    ->where('user_id', auth()->id())
    ->latest()
    ->limit($limit)
    ->get());

$unreadCount = once(fn () => \App\Models\InAppNotification::query()
    ->where('user_id', auth()->id())
    ->where('is_read', false)
    ->count());
@endphp

<div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
    <button
        type="button"
        @click="open = ! open"
        x-bind:aria-expanded="open"
        aria-label="Notifikasi"
        aria-haspopup="true"
        class="relative inline-flex h-9 w-9 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"
    >
        <x-icon name="bell" class="h-5 w-5" />

        @if ($unreadCount > 0)
            <span class="absolute -right-0.5 -top-0.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-secondary px-1 text-[10px] font-medium text-white">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 z-50 mt-2">
        <div class="w-80 overflow-hidden rounded-md border border-border bg-popover text-popover-foreground">
            <div class="flex items-center justify-between border-b border-border px-4 py-2.5">
                <span class="text-sm font-medium">Notifikasi</span>

                @if ($unreadCount > 0)
                    <span class="text-xs text-muted-foreground">{{ $unreadCount }} belum dibaca</span>
                @endif
            </div>

            @if ($notifications->isEmpty())
                <p class="px-4 py-6 text-center text-sm text-muted-foreground">Belum ada notifikasi.</p>
            @else
                <div class="flex flex-col p-1">
                    @foreach ($notifications as $notification)
                        <div class="flex gap-3 rounded-sm px-3 py-2.5 transition-colors hover:bg-accent">
                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground">
                                <x-icon
                                    :name="match ($notification->tipe) {
                                        'verifikasi' => 'user-check',
                                        'laporan' => 'triangle-alert',
                                        default => 'bell',
                                    }"
                                    class="h-4 w-4"
                                />
                            </span>

                            <div class="flex min-w-0 flex-col gap-0.5">
                                <span class="text-sm font-medium">{{ $notification->judul }}</span>
                                <span class="text-xs text-muted-foreground">{{ $notification->pesan }}</span>
                                <span class="text-xs text-muted-foreground">{{ $notification->created_at->locale('id')->diffForHumans() }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
