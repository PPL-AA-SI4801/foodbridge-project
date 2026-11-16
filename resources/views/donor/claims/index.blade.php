@php
    /*
     * Status di database bisa berbahasa Inggris (pending/approved/...) atau Indonesia.
     * Keduanya dipetakan ke kunci <x-status-badge> sesuai docs/design_system.md.
     */
    $statusAliases = [
        'pending' => 'menunggu',
        'approved' => 'disetujui',
        'rejected' => 'ditolak',
        'completed' => 'selesai',
        'cancelled' => 'dibatalkan',
        'canceled' => 'dibatalkan',
    ];
    $normalizeStatus = fn ($status) => $statusAliases[(string) $status] ?? (string) $status;

    // Ringkasan jumlah klaim per status (dari controller)
    $summary = [];
    foreach (collect($statusCounts ?? []) as $status => $total) {
        $key = $normalizeStatus($status);
        $summary[$key] = ($summary[$key] ?? 0) + (int) $total;
    }

    $activeFilter = $activeFilter ?? null;

    $stats = [
        'menunggu' => ['label' => 'Menunggu', 'hint' => 'Perlu ditinjau', 'icon' => 'clock'],
        'disetujui' => ['label' => 'Disetujui', 'hint' => 'Menunggu pengambilan', 'icon' => 'check'],
        'selesai' => ['label' => 'Selesai', 'hint' => 'Sudah diserahkan', 'icon' => 'clipboard-check'],
        'ditolak' => ['label' => 'Ditolak', 'hint' => 'Tidak disetujui', 'icon' => 'x'],
    ];

    $pendingCount = $summary['menunggu'] ?? 0;

    // Satu definisi kolom dipakai header dan baris supaya selalu sejajar di desktop.
    $columns = 'lg:grid-cols-[minmax(0,2.2fr)_minmax(0,1.6fr)_minmax(0,0.8fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1.7fr)]';
@endphp

<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Notifikasi flash --}}
        @if (session('success') || session('status'))
            <div class="mb-6 flex items-start gap-3 rounded-md border border-[#86EFAC] bg-[#DCFCE7] px-4 py-3 text-sm font-medium text-[#166534]" role="status">
                <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0" />
                <p>{{ session('success') ?? session('status') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 flex items-start gap-3 rounded-md border border-[#FCA5A5] bg-[#FEE2E2] px-4 py-3 text-sm font-medium text-[#B91C1C]" role="alert">
                <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0" />
                <p>{{ session('error') }}</p>
            </div>
        @endif

        {{-- Header halaman --}}
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary">Portal Donatur</p>
                <h1 class="mt-1.5 text-2xl font-semibold tracking-tight text-foreground">
                    {{ __('Kelola Klaim Masuk') }}
                </h1>
                <p class="mt-1.5 text-sm text-muted-foreground">
                    {{ __('Tinjau pengajuan klaim dari penerima manfaat untuk makanan yang Anda bagikan.') }}
                </p>
            </div>

            @if ($pendingCount > 0)
                <span class="inline-flex w-fit items-center gap-2 rounded-full border border-secondary/25 bg-secondary/5 px-3.5 py-1.5 text-xs font-semibold text-secondary">
                    <span class="h-1.5 w-1.5 rounded-full bg-secondary"></span>
                    {{ $pendingCount }} klaim menunggu keputusan
                </span>
            @endif
        </div>

        {{-- Ringkasan status (juga berfungsi sebagai filter) --}}
        <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ($stats as $key => $stat)
                @php
                    $isActive = $activeFilter === $key;
                    $needsAttention = $key === 'menunggu' && $pendingCount > 0;
                @endphp

                <a
                    href="{{ route('donor.claims.index', $isActive ? [] : ['status' => $key]) }}"
                    @class([
                        'group rounded-2xl border bg-card p-4 transition focus:outline-none focus:ring-2 focus:ring-primary/30 sm:p-5',
                        'border-primary ring-2 ring-primary/15' => $isActive,
                        'border-border hover:border-primary/30' => ! $isActive,
                    ])
                    @if ($isActive) aria-current="true" @endif
                >
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">{{ $stat['label'] }}</p>

                        <span @class([
                            'flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border',
                            'border-secondary/25 bg-secondary/10 text-secondary' => $needsAttention,
                            'border-border bg-muted text-muted-foreground' => ! $needsAttention,
                        ])>
                            <x-icon :name="$stat['icon']" class="h-4 w-4" />
                        </span>
                    </div>

                    <p class="mt-3 text-3xl font-semibold tracking-tight text-foreground">
                        {{ number_format($summary[$key] ?? 0, 0, ',', '.') }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ $stat['hint'] }}</p>
                </a>
            @endforeach
        </div>

        {{-- Daftar klaim --}}
        <section class="overflow-hidden rounded-2xl border border-border bg-card" aria-labelledby="claims-title">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border px-4 py-4 sm:px-6">
                <div>
                    <h2 id="claims-title" class="text-sm font-semibold text-foreground">Daftar pengajuan klaim dari penerima</h2>
                    <p class="mt-0.5 text-xs text-muted-foreground">Klaim yang menunggu keputusan ditampilkan paling atas.</p>
                </div>

                @if ($activeFilter && isset($stats[$activeFilter]))
                    <a
                        href="{{ route('donor.claims.index') }}"
                        class="inline-flex items-center gap-1.5 rounded-full border border-border bg-muted px-3 py-1 text-xs font-medium text-foreground transition hover:bg-accent"
                    >
                        Status: {{ $stats[$activeFilter]['label'] }}
                        <x-icon name="x" class="h-3 w-3 text-muted-foreground" />
                        <span class="sr-only">Hapus filter</span>
                    </a>
                @endif
            </div>

            @if ($claims->isEmpty())
                <div class="p-6 sm:p-10">
                    <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-background px-6 py-12 text-center">
                        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted text-muted-foreground">
                            <x-icon name="clipboard-check" class="h-7 w-7" />
                        </span>

                        @if ($activeFilter)
                            <h3 class="mt-4 text-sm font-semibold text-foreground">Tidak ada klaim dengan status ini</h3>
                            <p class="mt-1.5 max-w-xs text-xs leading-5 text-muted-foreground">
                                Coba pilih status lain atau tampilkan seluruh klaim.
                            </p>
                            <a href="{{ route('donor.claims.index') }}" class="mt-5 inline-flex items-center gap-1.5 rounded-lg bg-primary px-3.5 py-2 text-xs font-semibold text-primary-foreground transition hover:bg-primary/90">
                                Tampilkan semua klaim
                            </a>
                        @else
                            <h3 class="mt-4 text-sm font-semibold text-foreground">Belum ada klaim masuk</h3>
                            <p class="mt-1.5 max-w-xs text-xs leading-5 text-muted-foreground">
                                Pengajuan klaim dari penerima akan muncul di sini setelah mereka memilih makanan Anda.
                            </p>
                            <a href="{{ route('donor.food-posts.index') }}" class="mt-5 inline-flex items-center gap-1.5 rounded-lg bg-primary px-3.5 py-2 text-xs font-semibold text-primary-foreground transition hover:bg-primary/90">
                                Lihat postingan makanan
                            </a>
                        @endif
                    </div>
                </div>
            @else
                {{-- Baris judul kolom, hanya tampil di desktop --}}
                <div class="hidden gap-4 border-b border-border bg-muted px-6 py-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground lg:grid {{ $columns }}" aria-hidden="true">
                    <span>Makanan</span>
                    <span>Penerima</span>
                    <span>Jumlah</span>
                    <span>Tanggal</span>
                    <span>Status</span>
                    <span class="text-right">Aksi</span>
                </div>

                <ul class="divide-y divide-border" role="list">
                    @foreach ($claims as $claim)
                        @php
                            $post = $claim->post;
                            $recipient = $claim->recipient;
                            $statusKey = $normalizeStatus($claim->status);
                            $isPending = $statusKey === 'menunggu';
                            $exceedsStock = $isPending && $post !== null && $claim->jumlah_porsi > $post->sisa_porsi;
                        @endphp

                        <li
                            x-data="{ busy: false }"
                            @class([
                                'grid gap-3 px-4 py-4 transition-colors sm:px-6 lg:items-center lg:gap-4',
                                'hover:bg-muted/40' => ! $isPending,
                                'bg-secondary/[0.03] hover:bg-secondary/[0.06]' => $isPending,
                                $columns,
                            ])
                        >
                            {{-- Makanan --}}
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="relative h-14 w-14 shrink-0 overflow-hidden rounded-xl bg-muted">
                                    <x-icon name="leaf" class="absolute left-1/2 top-1/2 h-6 w-6 -translate-x-1/2 -translate-y-1/2 text-muted-foreground/35" />
                                    @if ($post?->foto)
                                        <img
                                            src="{{ asset('storage/'.$post->foto) }}"
                                            alt="{{ $post->judul }}"
                                            loading="lazy"
                                            class="relative h-full w-full object-cover"
                                            onerror="this.style.opacity='0'"
                                        />
                                    @endif
                                </div>

                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-foreground">
                                        {{ $post?->judul ?? 'Postingan tidak tersedia' }}
                                    </p>
                                    @if ($post)
                                        <p class="mt-0.5 flex items-center gap-1.5 truncate text-xs text-muted-foreground">
                                            <x-icon name="package" class="h-3 w-3 shrink-0" />
                                            Sisa {{ $post->sisa_porsi }} dari {{ $post->jumlah_porsi }} porsi
                                        </p>
                                    @endif
                                </div>
                            </div>

                            {{-- Penerima --}}
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-accent text-xs font-semibold text-primary" aria-hidden="true">
                                    {{ str($recipient?->nama_lembaga ?? '?')->substr(0, 1)->upper() }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-foreground" title="{{ $recipient?->alamat }}">
                                        {{ $recipient?->nama_lembaga ?? 'Penerima tidak diketahui' }}
                                    </p>
                                    @if ($recipient?->jenis_lembaga)
                                        <p class="mt-0.5 truncate text-xs text-muted-foreground">{{ $recipient->jenis_lembaga }}</p>
                                    @endif
                                </div>
                            </div>

                            {{-- Jumlah --}}
                            <div class="flex items-center justify-between gap-3 lg:block">
                                <span class="text-xs text-muted-foreground lg:sr-only">Jumlah</span>
                                <div class="text-right lg:text-left">
                                    <p class="text-sm text-muted-foreground">
                                        <span class="font-semibold text-foreground">{{ $claim->jumlah_porsi }}</span> porsi
                                    </p>
                                    @if ($exceedsStock)
                                        <p class="mt-0.5 text-[11px] font-medium text-[#B91C1C]">Melebihi sisa porsi</p>
                                    @endif
                                </div>
                            </div>

                            {{-- Tanggal --}}
                            <div class="flex items-center justify-between gap-3 lg:block">
                                <span class="text-xs text-muted-foreground lg:sr-only">Tanggal</span>
                                <p class="flex items-center gap-2 text-sm text-muted-foreground">
                                    {{ $claim->tanggal_klaim->format('d/m/Y') }}
                                    @if ($claim->tanggal_klaim->isToday())
                                        <span class="rounded-full bg-accent px-2 py-0.5 text-[10px] font-semibold text-primary">Hari ini</span>
                                    @endif
                                </p>
                            </div>

                            {{-- Status --}}
                            <div class="flex items-center justify-between gap-3 lg:block">
                                <span class="text-xs text-muted-foreground lg:sr-only">Status</span>
                                <x-status-badge :status="$statusKey" />
                            </div>

                            {{-- Aksi --}}
                            <div class="border-t border-border pt-3 lg:border-t-0 lg:pt-0">
                                @if ($isPending)
                                    <div class="flex items-center gap-2 lg:justify-end">
                                        <form
                                            method="POST"
                                            action="{{ route('donor.claims.approve', $claim) }}"
                                            class="flex-1 lg:flex-none"
                                            x-on:submit="busy = true"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                x-bind:disabled="busy"
                                                @disabled($exceedsStock)
                                                @if ($exceedsStock) title="Sisa porsi tidak mencukupi untuk klaim ini." @endif
                                                class="inline-flex h-9 w-full items-center justify-center gap-1.5 rounded-lg bg-primary px-3.5 text-xs font-semibold text-primary-foreground transition hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                <x-icon name="check" class="h-3.5 w-3.5" />
                                                Setujui
                                            </button>
                                        </form>

                                        <form
                                            method="POST"
                                            action="{{ route('donor.claims.reject', $claim) }}"
                                            class="flex-1 lg:flex-none"
                                            x-on:submit="if (! confirm('Tolak klaim ini? Penerima akan diberi tahu.')) { $event.preventDefault(); return; } busy = true"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                x-bind:disabled="busy"
                                                class="inline-flex h-9 w-full items-center justify-center gap-1.5 rounded-lg border border-[#FCA5A5] bg-card px-3.5 text-xs font-semibold text-[#B91C1C] transition hover:bg-[#FEE2E2] focus:outline-none focus:ring-2 focus:ring-[#B91C1C]/20 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                <x-icon name="x" class="h-3.5 w-3.5" />
                                                Tolak
                                            </button>
                                        </form>
                                    </div>
                                @elseif ($statusKey === 'disetujui')
                                    <p class="text-xs leading-5 text-muted-foreground lg:text-right">
                                        Disetujui{{ $claim->approved_at ? ' pada '.$claim->approved_at->format('d/m/Y H:i') : '' }}.
                                        <span class="block">Menunggu pengambilan.</span>
                                    </p>
                                @elseif ($statusKey === 'selesai')
                                    <p class="text-xs leading-5 text-muted-foreground lg:text-right">
                                        Diserahkan{{ $claim->completed_at ? ' pada '.$claim->completed_at->format('d/m/Y H:i') : '' }}.
                                    </p>
                                @elseif ($statusKey === 'ditolak')
                                    <p class="text-xs leading-5 text-muted-foreground lg:text-right">Klaim ditolak.</p>
                                @else
                                    <p class="text-xs text-muted-foreground lg:text-right">-</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>

                @if ($claims->hasPages())
                    <div class="border-t border-border px-4 py-4 sm:px-6">
                        {{ $claims->onEachSide(1)->links() }}
                    </div>
                @endif
            @endif
        </section>
    </div>
</x-app-layout>