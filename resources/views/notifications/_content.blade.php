@php
    $user = auth()->user();

    $roleEyebrow = match (true) {
        $user->hasRole(\App\Enums\Role::Donor) => 'Portal Donatur',
        $user->hasRole(\App\Enums\Role::Penerima) => 'Penerima / Yayasan',
        default => 'Administrator',
    };

    $roleDescription = match (true) {
        $user->hasRole(\App\Enums\Role::Donor) => 'Pantau pembaruan klaim makanan dan aktivitas akun donatur Anda.',
        $user->hasRole(\App\Enums\Role::Penerima) => 'Pantau status klaim dan informasi penting untuk lembaga Anda.',
        default => 'Pantau aktivitas verifikasi akun dan laporan yang masuk.',
    };

    $today = now()->toDateString();
    $yesterday = now()->subDay()->toDateString();
    $showAllLink = $filter === 'unread' && $totalCount > 0;
    $emptyActionUrl = $showAllLink ? route('notifications.index') : route($user->dashboardRouteName());
    $emptyActionLabel = $showAllLink ? 'Lihat semua notifikasi' : 'Kembali ke dashboard';
    $emptyActionIcon = $showAllLink ? 'bell' : 'layout-dashboard';

    $groups = $notifications->getCollection()
        ->groupBy(fn ($notification) => $notification->created_at?->toDateString() ?? 'tanpa-tanggal');
@endphp

<div class="mx-auto max-w-4xl">
    {{-- Judul halaman --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary">{{ $roleEyebrow }}</p>
            <h1 class="mt-1.5 text-2xl font-semibold tracking-tight text-foreground">Notifikasi</h1>
            <p class="mt-1.5 max-w-2xl text-sm leading-6 text-muted-foreground">{{ $roleDescription }}</p>
        </div>

        <span class="inline-flex w-fit shrink-0 items-center gap-2 rounded-full border border-border bg-card px-3 py-1.5 text-xs font-medium text-muted-foreground">
            <span @class([
                'h-2 w-2 rounded-full',
                'bg-secondary' => $unreadCount > 0,
                'bg-muted-foreground/40' => $unreadCount === 0,
            ]) aria-hidden="true"></span>
            {{ $unreadCount > 0 ? $unreadCount.' belum dibaca' : 'Semua sudah dibaca' }}
        </span>
    </div>

    {{-- Daftar notifikasi --}}
    <section class="overflow-hidden rounded-2xl border border-border bg-card" aria-labelledby="notification-list-title">
        <div class="flex flex-col gap-3 border-b border-border px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 id="notification-list-title" class="text-sm font-semibold text-foreground">Daftar notifikasi</h2>
                <p class="mt-0.5 text-xs text-muted-foreground">Diurutkan dari yang terbaru.</p>
            </div>

            <nav aria-label="Filter notifikasi" class="inline-flex w-full shrink-0 items-center gap-1 rounded-lg border border-border bg-background p-1 sm:w-auto">
                <a
                    href="{{ route('notifications.index') }}"
                    @if ($filter === 'all') aria-current="page" @endif
                    @class([
                        'inline-flex flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-md px-3 py-1.5 text-xs font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 sm:flex-none',
                        'bg-primary text-primary-foreground' => $filter === 'all',
                        'text-muted-foreground hover:bg-accent hover:text-foreground' => $filter !== 'all',
                    ])
                >
                    Semua
                    <span @class([
                        'inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[10px] font-semibold',
                        'bg-white/15 text-primary-foreground' => $filter === 'all',
                        'bg-muted text-muted-foreground' => $filter !== 'all',
                    ])>{{ $totalCount }}</span>
                </a>

                <a
                    href="{{ route('notifications.index', ['filter' => 'unread']) }}"
                    @if ($filter === 'unread') aria-current="page" @endif
                    @class([
                        'inline-flex flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-md px-3 py-1.5 text-xs font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 sm:flex-none',
                        'bg-primary text-primary-foreground' => $filter === 'unread',
                        'text-muted-foreground hover:bg-accent hover:text-foreground' => $filter !== 'unread',
                    ])
                >
                    Belum dibaca
                    <span @class([
                        'inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[10px] font-semibold',
                        'bg-white/15 text-primary-foreground' => $filter === 'unread',
                        'bg-muted text-muted-foreground' => $filter !== 'unread',
                    ])>{{ $unreadCount }}</span>
                </a>
            </nav>
        </div>

        @if ($notifications->isEmpty())
            <div class="p-6 sm:p-10">
                <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-background px-6 py-12 text-center">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted text-muted-foreground">
                        <x-icon name="bell" class="h-7 w-7" />
                    </span>

                    <h2 class="mt-4 text-sm font-semibold text-foreground">
                        {{ $filter === 'unread' ? 'Tidak ada notifikasi yang belum dibaca' : 'Belum ada notifikasi' }}
                    </h2>

                    <p class="mt-1.5 max-w-sm text-xs leading-5 text-muted-foreground">
                        @if ($filter === 'unread' && $totalCount > 0)
                            Semua notifikasi sudah dibaca. Notifikasi baru akan muncul di sini.
                        @elseif ($filter === 'unread')
                            Belum ada notifikasi baru untuk akun Anda.
                        @else
                            Informasi tentang klaim, verifikasi, dan aktivitas akun akan tampil di halaman ini.
                        @endif
                    </p>

                    <x-button :href="$emptyActionUrl" size="sm" class="mt-5">
                        <x-slot:icon>
                            <x-icon :name="$emptyActionIcon" />
                        </x-slot:icon>
                        {{ $emptyActionLabel }}
                    </x-button>
                </div>
            </div>
        @else
            <div>
                @foreach ($groups as $dateKey => $items)
                    @php
                        $dateLabel = match (true) {
                            $dateKey === $today => 'Hari ini',
                            $dateKey === $yesterday => 'Kemarin',
                            $dateKey === 'tanpa-tanggal' => 'Tanggal tidak diketahui',
                            default => \Illuminate\Support\Carbon::parse($dateKey)->locale('id')->translatedFormat('d F Y'),
                        };
                    @endphp

                    <section aria-labelledby="notification-group-{{ $loop->index }}">
                        <div class="flex items-center justify-between gap-3 border-b border-border bg-muted/60 px-4 py-2.5 sm:px-6">
                            <h3 id="notification-group-{{ $loop->index }}" class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">
                                {{ $dateLabel }}
                            </h3>
                            <span class="text-[11px] text-muted-foreground">{{ $items->count() }} notifikasi</span>
                        </div>

                        <ul role="list" class="divide-y divide-border">
                            @foreach ($items as $notification)
                                @php
                                    $meta = \App\Support\NotificationPresenter::for($notification->tipe);
                                @endphp

                                <li @class([
                                    'relative flex gap-3 px-4 py-4 sm:gap-4 sm:px-6',
                                    'bg-accent/30' => ! $notification->is_read,
                                ])>
                                    @if (! $notification->is_read)
                                        <span class="absolute inset-y-0 left-0 w-0.5 bg-secondary" aria-hidden="true"></span>
                                    @endif

                                    <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $meta['iconClass'] }}">
                                        <x-icon :name="$meta['icon']" class="h-5 w-5" />
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-1">
                                            <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">
                                                {{ $meta['label'] }}
                                            </p>

                                            @if ($notification->created_at)
                                                <time
                                                    datetime="{{ $notification->created_at->toIso8601String() }}"
                                                    title="{{ $notification->created_at->locale('id')->translatedFormat('d F Y, H:i') }}"
                                                    class="shrink-0 text-xs text-muted-foreground"
                                                >
                                                    {{ $notification->created_at->locale('id')->diffForHumans() }}
                                                </time>
                                            @endif
                                        </div>

                                        <p class="mt-1 break-words text-sm font-semibold leading-5 text-foreground">
                                            {{ $notification->judul }}
                                        </p>

                                        <p class="mt-1 break-words text-sm leading-6 text-muted-foreground">
                                            {{ $notification->pesan }}
                                        </p>

                                        @if (! $notification->is_read)
                                            <span class="mt-2 inline-flex items-center gap-1.5 text-[11px] font-semibold text-secondary">
                                                <span class="h-1.5 w-1.5 rounded-full bg-secondary" aria-hidden="true"></span>
                                                Belum dibaca
                                            </span>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>
        @endif

        @if ($notifications->hasPages())
            <div class="border-t border-border px-4 py-4 sm:px-6">
                {{ $notifications->onEachSide(1)->links() }}
            </div>
        @endif
    </section>
</div>
