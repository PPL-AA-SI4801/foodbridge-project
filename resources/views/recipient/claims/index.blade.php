@php
    $normalizeStatus = fn ($status) => \App\Support\ClaimStatus::normalize($status);
    $methodLabel = function (?string $method): ?string {
        if ($method === null || trim($method) === '') {
            return null;
        }

        $method = strtolower($method);

        return str_contains($method, 'kurir') || str_contains($method, 'courier')
            ? 'Kurir'
            : 'Mandiri';
    };
    $donorCity = function ($donor): ?string {
        $address = trim((string) ($donor?->alamat ?? ''));

        if ($address === '') {
            return null;
        }

        $parts = array_map('trim', explode(',', $address));

        return end($parts) ?: null;
    };

    $summaryItems = ['menunggu', 'disetujui', 'selesai', 'ditolak'];
    $summaryTotal = array_sum($statusCounts);
    $filterActive = ($filters['q'] ?? '') !== '' || ($filters['status'] ?? null) !== null;

    $columns = 'lg:grid-cols-[minmax(0,0.35fr)_minmax(0,2.1fr)_minmax(0,0.6fr)_minmax(0,1.4fr)_minmax(0,0.8fr)_minmax(0,0.9fr)_minmax(0,0.9fr)_minmax(0,0.8fr)]';
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
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary">Penerima / Yayasan</p>
                <h1 class="mt-1.5 text-2xl font-semibold tracking-tight text-foreground">
                    {{ __('Klaim Saya') }}
                </h1>
                <p class="mt-1.5 text-sm text-muted-foreground">
                    {{ __('Pantau status klaim makanan dan sisa kuota harian lembaga Anda.') }}
                </p>
            </div>

            <x-button href="{{ route('recipient.catalog.index') }}" size="md" class="w-full sm:w-auto">
                <x-slot:icon>
                    <x-icon name="search" />
                </x-slot:icon>
                {{ __('Cari makanan') }}
            </x-button>
        </div>

        {{-- Kuota harian + ringkasan status --}}
        <div class="mb-6 grid gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
            <section class="rounded-2xl border border-border bg-card p-6" aria-labelledby="quota-title">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-accent text-primary">
                            <x-icon name="package" class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 id="quota-title" class="text-sm font-semibold text-foreground">Kuota Harian</h2>
                            <p class="mt-0.5 text-xs text-muted-foreground">Maksimal {{ $quota['limit'] }} klaim per hari. Klaim ditolak tidak dihitung.</p>
                        </div>
                    </div>

                    <span @class([
                        'inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold',
                        'bg-secondary/10 text-secondary' => $quota['exhausted'],
                        'bg-accent text-primary' => ! $quota['exhausted'],
                    ])>
                        <span @class([
                            'h-1.5 w-1.5 rounded-full',
                            'bg-secondary' => $quota['exhausted'],
                            'bg-primary' => ! $quota['exhausted'],
                        ])></span>
                        {{ $quota['exhausted'] ? 'Kuota habis' : 'Masih tersedia' }}
                    </span>
                </div>

                <div class="mt-6 flex flex-wrap items-end gap-x-3 gap-y-1">
                    <p class="text-4xl font-semibold tracking-tight text-foreground" data-quota="{{ $quota['remaining'].'/'.$quota['limit'] }}">
                        {{ $quota['remaining'] }}/{{ $quota['limit'] }}
                    </p>
                    <p class="pb-1 text-sm text-muted-foreground">sisa klaim hari ini</p>
                </div>

                {{-- Satu segmen untuk setiap jatah klaim --}}
                <div
                    class="mt-4 grid grid-cols-3 gap-2"
                    role="img"
                    aria-label="{{ $quota['used'] }} dari {{ $quota['limit'] }} klaim hari ini sudah terpakai"
                >
                    @for ($slot = 1; $slot <= $quota['limit']; $slot++)
                        <span @class([
                            'h-2.5 rounded-full',
                            'bg-secondary' => $slot <= $quota['used'] && $quota['exhausted'],
                            'bg-primary' => $slot <= $quota['used'] && ! $quota['exhausted'],
                            'border border-border bg-muted' => $slot > $quota['used'],
                        ])></span>
                    @endfor
                </div>

                @if ($quota['exhausted'])
                    <div class="mt-5 flex items-start gap-3 rounded-xl border border-secondary/25 bg-secondary/5 px-3.5 py-3 text-xs leading-5 text-foreground">
                        <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0 text-secondary" />
                        <p>
                            <span class="font-semibold">Kuota klaim hari ini sudah habis.</span>
                            Anda dapat mengajukan klaim lagi besok.
                        </p>
                    </div>
                @else
                    <p class="mt-4 text-xs leading-5 text-muted-foreground">
                        {{ $quota['used'] }} dari {{ $quota['limit'] }} klaim sudah terpakai hari ini.
                    </p>
                @endif
            </section>

            <section class="rounded-2xl border border-border bg-card p-6" aria-labelledby="summary-title">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="summary-title" class="text-sm font-semibold text-foreground">Ringkasan klaim</h2>
                    <span class="text-xs text-muted-foreground">{{ number_format($summaryTotal, 0, ',', '.') }} klaim seluruhnya</span>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3">
                    @foreach ($summaryItems as $item)
                        @php
                            $summaryQuery = array_filter([
                                'q' => ($filters['q'] ?? '') !== '' ? $filters['q'] : null,
                                'status' => ($filters['status'] ?? null) === $item ? null : $item,
                            ]);
                        @endphp

                        <a
                            href="{{ route('recipient.claims.index', $summaryQuery) }}"
                            @class([
                                'rounded-xl border bg-background p-3.5 transition focus:outline-none focus:ring-2 focus:ring-primary/30',
                                'border-primary ring-2 ring-primary/15' => ($filters['status'] ?? null) === $item,
                                'border-border hover:border-primary/30' => ($filters['status'] ?? null) !== $item,
                            ])
                            @if (($filters['status'] ?? null) === $item) aria-current="true" @endif
                        >
                            <x-status-badge :status="$item" />
                            <p class="mt-2.5 text-2xl font-semibold tracking-tight text-foreground">
                                {{ number_format($statusCounts[$item] ?? 0, 0, ',', '.') }}
                            </p>
                        </a>
                    @endforeach
                </div>
            </section>
        </div>

        {{-- Daftar klaim --}}
        <section class="overflow-hidden rounded-2xl border border-border bg-card" aria-labelledby="history-title">
            <div class="border-b border-border px-4 py-4 sm:px-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h2 id="history-title" class="text-sm font-semibold text-foreground">Daftar klaim makanan</h2>
                        <p class="mt-0.5 text-xs text-muted-foreground">Klaim terbaru ditampilkan paling atas.</p>
                    </div>

                    <form method="GET" action="{{ route('recipient.claims.index') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <div class="relative min-w-0 sm:w-64">
                            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <input
                                type="search"
                                name="q"
                                value="{{ $filters['q'] }}"
                                placeholder="Cari makanan atau donor..."
                                aria-label="Cari nama makanan atau donor"
                                class="h-9 w-full rounded-md border border-input bg-card pl-9 pr-3 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                            >
                        </div>

                        <select
                            name="status"
                            aria-label="Filter status klaim"
                            class="h-9 rounded-md border border-input bg-card px-3 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                        >
                            <option value="">Semua status</option>
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? null) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>

                        <x-button type="submit" size="sm">
                            <x-slot:icon>
                                <x-icon name="filter" />
                            </x-slot:icon>
                            {{ __('Terapkan') }}
                        </x-button>

                        @if ($filterActive)
                            <a
                                href="{{ route('recipient.claims.index') }}"
                                class="inline-flex h-8 items-center justify-center rounded-md border border-border bg-card px-3 text-xs font-medium text-muted-foreground transition hover:bg-accent hover:text-foreground"
                            >
                                Reset
                            </a>
                        @endif
                    </form>
                </div>
            </div>

            @if ($claims->isEmpty())
                <div class="p-6 sm:p-10">
                    @if ($filterActive)
                        <x-layout.empty-state
                            title="Tidak ada klaim yang cocok"
                            description="Coba ubah kata kunci pencarian atau pilih status klaim yang lain."
                            class="rounded-2xl border border-dashed border-border bg-background"
                        >
                            <x-slot:icon>
                                <x-icon name="search" class="h-12 w-12" />
                            </x-slot:icon>

                            <x-slot:actions>
                                <x-button href="{{ route('recipient.claims.index') }}" size="sm" class="btn-secondary">
                                    {{ __('Tampilkan semua klaim') }}
                                </x-button>
                                <x-button href="{{ route('recipient.catalog.index') }}" size="sm">
                                    <x-slot:icon>
                                        <x-icon name="search" />
                                    </x-slot:icon>
                                    {{ __('Buka katalog makanan') }}
                                </x-button>
                            </x-slot:actions>
                        </x-layout.empty-state>
                    @else
                        <x-layout.empty-state
                            title="Belum ada klaim makanan"
                            description="Pilih makanan dari katalog, ajukan klaim, lalu pantau statusnya di halaman ini."
                            class="rounded-2xl border border-dashed border-border bg-background"
                        >
                            <x-slot:icon>
                                <x-icon name="clipboard-check" class="h-12 w-12" />
                            </x-slot:icon>

                            <x-slot:actions>
                                <x-button href="{{ route('recipient.catalog.index') }}" size="sm">
                                    <x-slot:icon>
                                        <x-icon name="search" />
                                    </x-slot:icon>
                                    {{ __('Buka katalog makanan') }}
                                </x-button>
                            </x-slot:actions>
                        </x-layout.empty-state>
                    @endif
                </div>
            @else
                {{-- Baris judul kolom, hanya tampil di desktop --}}
                <div class="hidden gap-4 border-b border-border bg-muted px-6 py-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground lg:grid {{ $columns }}" aria-hidden="true">
                    <span>No</span>
                    <span>Makanan</span>
                    <span>Porsi</span>
                    <span>Donor</span>
                    <span>Metode</span>
                    <span>Tgl Klaim</span>
                    <span>Status</span>
                    <span class="text-right">Aksi</span>
                </div>

                <ul class="divide-y divide-border" role="list">
                    @foreach ($claims as $claim)
                        @php
                            $post = $claim->post;
                            $statusKey = $normalizeStatus($claim->status);
                            $method = $methodLabel($claim->metode_pengambilan);
                            $city = $donorCity($post?->donor);
                            $rowNumber = ($claims->firstItem() ?? 1) + $loop->index;
                        @endphp

                        <li class="grid gap-3 px-4 py-4 transition-colors hover:bg-muted/40 sm:px-6 lg:items-center lg:gap-4 {{ $columns }}">
                            {{-- No --}}
                            <div class="flex items-center justify-between gap-3 lg:block lg:text-center">
                                <span class="text-xs text-muted-foreground lg:hidden">No</span>
                                <span class="text-sm text-muted-foreground">{{ $rowNumber }}</span>
                            </div>

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
                                    <p class="mt-0.5 truncate text-xs text-muted-foreground">
                                        {{ $post?->category?->nama_kategori ?? 'Tanpa kategori' }}
                                    </p>
                                </div>
                            </div>

                            {{-- Porsi --}}
                            <div class="flex items-center justify-between gap-3 lg:block">
                                <span class="text-xs text-muted-foreground lg:hidden">Porsi</span>
                                <p class="text-sm text-muted-foreground">
                                    <span class="font-semibold text-foreground">{{ $claim->jumlah_porsi }}</span> porsi
                                </p>
                            </div>

                            {{-- Donor --}}
                            <div class="flex min-w-0 items-center justify-between gap-3 lg:block">
                                <span class="text-xs text-muted-foreground lg:hidden">Donor</span>
                                <div class="min-w-0 text-right lg:text-left">
                                    <p class="truncate text-sm font-medium text-foreground">
                                        {{ $post?->donor?->nama_usaha ?? 'Donatur FoodBridge' }}
                                    </p>
                                    <p class="mt-0.5 truncate text-xs text-muted-foreground">{{ $city ?? '-' }}</p>
                                </div>
                            </div>

                            {{-- Metode --}}
                            <div class="flex items-center justify-between gap-3 lg:block">
                                <span class="text-xs text-muted-foreground lg:hidden">Metode</span>
                                <p class="text-sm text-muted-foreground">{{ $method ?? '-' }}</p>
                            </div>

                            {{-- Tanggal --}}
                            <div class="flex items-center justify-between gap-3 lg:block">
                                <span class="text-xs text-muted-foreground lg:hidden">Tgl Klaim</span>
                                <p class="flex items-center gap-2 text-sm text-muted-foreground lg:block">
                                    {{ $claim->tanggal_klaim->format('d/m/Y') }}
                                    @if ($claim->tanggal_klaim->isToday())
                                        <span class="rounded-full bg-accent px-2 py-0.5 text-[10px] font-semibold text-primary">Hari ini</span>
                                    @endif
                                </p>
                            </div>

                            {{-- Status --}}
                            <div class="flex items-center justify-between gap-3 lg:block">
                                <span class="text-xs text-muted-foreground lg:hidden">Status</span>
                                <x-status-badge :status="$statusKey" />
                            </div>

                            {{-- Aksi --}}
                            <div class="border-t border-border pt-3 lg:border-t-0 lg:pt-0 lg:text-right">
                                <button
                                    type="button"
                                    x-data
                                    x-on:click="$dispatch('open-modal', 'claim-detail-{{ $claim->id }}')"
                                    dusk="claim-detail-{{ $claim->id }}"
                                    class="inline-flex h-9 w-full items-center justify-center gap-1.5 rounded-lg border border-border bg-card px-3.5 text-xs font-semibold text-foreground transition hover:bg-accent focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2 lg:w-auto"
                                >
                                    <x-icon name="eye" class="h-3.5 w-3.5" />
                                    Detail
                                </button>
                            </div>

                            {{-- Modal detail klaim --}}
                        <x-modal name="claim-detail-{{ $claim->id }}" maxWidth="lg">
                            <div class="p-6" dusk="claim-detail-modal-{{ $claim->id }}">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-primary">Detail Klaim</p>
                                        <h3 class="mt-1.5 truncate text-lg font-semibold text-foreground">
                                            {{ $post?->judul ?? 'Postingan tidak tersedia' }}
                                        </h3>
                                        <p class="mt-1 text-sm text-muted-foreground">
                                            {{ $post?->donor?->nama_usaha ?? 'Donatur FoodBridge' }}@if ($city) · {{ $city }}@endif
                                        </p>
                                    </div>

                                    <x-status-badge :status="$statusKey" class="shrink-0" />
                                </div>

                                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Jumlah porsi</dt>
                                        <dd class="mt-1 text-sm font-medium text-foreground">{{ $claim->jumlah_porsi }} porsi</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Tanggal klaim</dt>
                                        <dd class="mt-1 text-sm font-medium text-foreground">{{ $claim->tanggal_klaim->format('d/m/Y') }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Metode pengambilan</dt>
                                        <dd class="mt-1 text-sm font-medium text-foreground">{{ $method ?? 'Belum dipilih' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Status</dt>
                                        <dd class="mt-1"><x-status-badge :status="$statusKey" /></dd>
                                    </div>
                                    @if ($claim->approved_at)
                                        <div>
                                            <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Waktu disetujui</dt>
                                            <dd class="mt-1 text-sm font-medium text-foreground">{{ $claim->approved_at->format('d/m/Y H:i') }}</dd>
                                        </div>
                                    @endif
                                    @if ($claim->completed_at)
                                        <div>
                                            <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Waktu selesai</dt>
                                            <dd class="mt-1 text-sm font-medium text-foreground">{{ $claim->completed_at->format('d/m/Y H:i') }}</dd>
                                        </div>
                                    @endif
                                </dl>

                                <div class="mt-5 rounded-xl border border-border bg-background px-4 py-3 text-sm leading-6 text-muted-foreground">
                                    @if ($statusKey === 'menunggu')
                                        <p class="flex items-start gap-2">
                                            <x-icon name="clock" class="mt-1 h-4 w-4 shrink-0" />
                                            <span>Klaim sedang menunggu persetujuan donor.</span>
                                        </p>
                                    @elseif ($statusKey === 'disetujui')
                                        <p class="flex items-start gap-2">
                                            <x-icon name="shield-check" class="mt-1 h-4 w-4 shrink-0 text-primary" />
                                            <span>Klaim telah disetujui donor. Silakan lanjutkan proses pengambilan sesuai metode yang dipilih.</span>
                                        </p>
                                    @elseif ($statusKey === 'selesai')
                                        <p class="flex items-start gap-2">
                                            <x-icon name="check" class="mt-1 h-4 w-4 shrink-0 text-[#166534]" />
                                            <span>Makanan sudah diserahkan oleh donor. Klaim selesai.</span>
                                        </p>
                                    @elseif ($statusKey === 'ditolak')
                                        <p class="flex items-start gap-2">
                                            <x-icon name="x" class="mt-1 h-4 w-4 shrink-0 text-[#B91C1C]" />
                                            <span>Klaim ditolak donor dan tidak dihitung ke kuota harian Anda.</span>
                                        </p>
                                    @elseif ($statusKey === 'dibatalkan')
                                        <p class="flex items-start gap-2">
                                            <x-icon name="x" class="mt-1 h-4 w-4 shrink-0" />
                                            <span>Klaim dibatalkan.</span>
                                        </p>
                                    @else
                                        <p>-</p>
                                    @endif
                                </div>

                                @if ($claim->kode_verifikasi)
                                    <div class="mt-4 rounded-xl border border-border bg-card px-4 py-3">
                                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Kode verifikasi</p>
                                        <p class="mt-1 font-mono text-lg font-semibold tracking-[0.2em] text-foreground">{{ $claim->kode_verifikasi }}</p>
                                    </div>
                                @endif

                                @if ($claim->courierDetail)
                                    <div class="mt-4 rounded-xl border border-border bg-card px-4 py-3">
                                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Detail kurir</p>
                                        <dl class="mt-2 grid gap-2 text-sm sm:grid-cols-2">
                                            <div class="flex justify-between gap-3 sm:block">
                                                <dt class="text-muted-foreground">Platform</dt>
                                                <dd class="font-medium text-foreground">{{ $claim->courierDetail->platform }}</dd>
                                            </div>
                                            <div class="flex justify-between gap-3 sm:block">
                                                <dt class="text-muted-foreground">Nama pengemudi</dt>
                                                <dd class="font-medium text-foreground">{{ $claim->courierDetail->nama_pengemudi }}</dd>
                                            </div>
                                            <div class="flex justify-between gap-3 sm:block">
                                                <dt class="text-muted-foreground">No. kendaraan</dt>
                                                <dd class="font-medium text-foreground">{{ $claim->courierDetail->no_kendaraan }}</dd>
                                            </div>
                                            <div class="flex justify-between gap-3 sm:block">
                                                <dt class="text-muted-foreground">Kode kurir</dt>
                                                <dd class="font-mono font-medium text-foreground">{{ $claim->courierDetail->kode_verifikasi_kurir }}</dd>
                                            </div>
                                        </dl>
                                    </div>
                                @endif

                                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                    <x-button
                                        type="button"
                                        size="sm"
                                        class="btn-secondary"
                                        x-data
                                        x-on:click="$dispatch('close-modal', 'claim-detail-{{ $claim->id }}')"
                                    >
                                        {{ __('Tutup') }}
                                    </x-button>

                                    @if (\Illuminate\Support\Facades\Route::has('recipient.reviews.index'))
                                        <x-button href="{{ route('recipient.reviews.index') }}" size="sm">
                                            <x-slot:icon>
                                                <x-icon name="star" />
                                            </x-slot:icon>
                                            {{ __('Beri Ulasan') }}
                                        </x-button>
                                    @endif
                                </div>
                            </div>
                        </x-modal>
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
