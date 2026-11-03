@php

    $statusAliases = [
        'pending' => 'menunggu',
        'approved' => 'disetujui',
        'rejected' => 'ditolak',
        'completed' => 'selesai',
        'cancelled' => 'dibatalkan',
        'canceled' => 'dibatalkan',
    ];
    $normalizeStatus = fn ($status) => $statusAliases[(string) $status] ?? (string) $status;

    $methodLabel = function (?string $method): ?string {
        if ($method === null || $method === '') {
            return null;
        }

        $method = strtolower($method);

        return str_contains($method, 'kurir') || str_contains($method, 'courier')
            ? 'Kurir online'
            : 'Ambil sendiri';
    };

    $dailyQuotaLimit = 3;
    $usedQuota = min($dailyQuotaLimit, (int) ($dailyClaimsCount ?? ($dailyQuotaLimit - $remainingQuota)));
    $quotaExhausted = $remainingQuota <= 0;

    $summary = [];
    foreach (collect($statusCounts ?? []) as $status => $total) {
        $key = $normalizeStatus($status);
        $summary[$key] = ($summary[$key] ?? 0) + (int) $total;
    }

    $summaryItems = ['menunggu', 'disetujui', 'selesai', 'ditolak'];

    $columns = 'lg:grid-cols-[minmax(0,2.4fr)_minmax(0,0.8fr)_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1.7fr)]';
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
                            <h2 id="quota-title" class="text-sm font-semibold text-foreground">Kuota klaim hari ini</h2>
                            <p class="mt-0.5 text-xs text-muted-foreground">Diperbarui setiap hari, maksimal {{ $dailyQuotaLimit }} klaim.</p>
                        </div>
                    </div>

                    <span @class([
                        'inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold',
                        'bg-secondary/10 text-secondary' => $quotaExhausted,
                        'bg-accent text-primary' => ! $quotaExhausted,
                    ])>
                        <span @class([
                            'h-1.5 w-1.5 rounded-full',
                            'bg-secondary' => $quotaExhausted,
                            'bg-primary' => ! $quotaExhausted,
                        ])></span>
                        {{ $quotaExhausted ? 'Kuota habis' : 'Masih tersedia' }}
                    </span>
                </div>

                <div class="mt-6 flex items-baseline gap-2">
                    <span class="text-4xl font-semibold tracking-tight text-foreground">{{ $remainingQuota }}</span>
                    <span class="text-sm text-muted-foreground">dari {{ $dailyQuotaLimit }} klaim tersisa</span>
                </div>

                {{-- Satu segmen untuk setiap jatah klaim --}}
                <div
                    class="mt-4 grid grid-cols-3 gap-2"
                    role="img"
                    aria-label="{{ $usedQuota }} dari {{ $dailyQuotaLimit }} klaim hari ini sudah terpakai"
                >
                    @for ($slot = 1; $slot <= $dailyQuotaLimit; $slot++)
                        <span @class([
                            'h-2.5 rounded-full',
                            'bg-secondary' => $slot <= $usedQuota && $quotaExhausted,
                            'bg-primary' => $slot <= $usedQuota && ! $quotaExhausted,
                            'border border-border bg-muted' => $slot > $usedQuota,
                        ])></span>
                    @endfor
                </div>

                @if ($quotaExhausted)
                    <div class="mt-5 flex items-start gap-3 rounded-xl border border-secondary/25 bg-secondary/5 px-3.5 py-3 text-xs leading-5 text-foreground">
                        <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0 text-secondary" />
                        <p>
                            <span class="font-semibold">Kuota klaim hari ini sudah habis.</span>
                            Anda dapat mengajukan klaim lagi besok.
                        </p>
                    </div>
                @else
                    <p class="mt-4 text-xs leading-5 text-muted-foreground">
                        {{ $usedQuota }} klaim sudah terpakai hari ini. Klaim yang ditolak donor tidak mengurangi kuota.
                    </p>
                @endif
            </section>

            <section class="rounded-2xl border border-border bg-card p-6" aria-labelledby="summary-title">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="summary-title" class="text-sm font-semibold text-foreground">Ringkasan klaim</h2>
                    <span class="text-xs text-muted-foreground">{{ number_format($claims->total(), 0, ',', '.') }} klaim seluruhnya</span>
                </div>

                <dl class="mt-4 grid grid-cols-2 gap-3">
                    @foreach ($summaryItems as $item)
                        <div class="rounded-xl border border-border bg-background p-3.5">
                            <dt><x-status-badge :status="$item" /></dt>
                            <dd class="mt-2.5 text-2xl font-semibold tracking-tight text-foreground">
                                {{ number_format($summary[$item] ?? 0, 0, ',', '.') }}
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        </div>

        {{-- Riwayat klaim --}}
        <section class="overflow-hidden rounded-2xl border border-border bg-card" aria-labelledby="history-title">
            <div class="flex items-center justify-between gap-3 border-b border-border px-4 py-4 sm:px-6">
                <div>
                    <h2 id="history-title" class="text-sm font-semibold text-foreground">Riwayat pengajuan klaim</h2>
                    <p class="mt-0.5 text-xs text-muted-foreground">Klaim terbaru ditampilkan paling atas.</p>
                </div>
            </div>

            @if ($claims->isEmpty())
                <div class="p-6 sm:p-10">
                    <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-background px-6 py-12 text-center">
                        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted text-muted-foreground">
                            <x-icon name="clipboard-check" class="h-7 w-7" />
                        </span>
                        <h3 class="mt-4 text-sm font-semibold text-foreground">Belum ada klaim makanan</h3>
                        <p class="mt-1.5 max-w-xs text-xs leading-5 text-muted-foreground">
                            Pilih makanan dari katalog, ajukan klaim, lalu pantau statusnya di halaman ini.
                        </p>
                        <x-button href="{{ route('recipient.catalog.index') }}" size="sm" class="mt-5">
                            <x-slot:icon>
                                <x-icon name="search" />
                            </x-slot:icon>
                            {{ __('Buka katalog makanan') }}
                        </x-button>
                    </div>
                </div>
            @else
                {{-- Baris judul kolom, hanya tampil di desktop --}}
                <div class="hidden gap-4 border-b border-border bg-muted px-6 py-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground lg:grid {{ $columns }}" aria-hidden="true">
                    <span>Makanan</span>
                    <span>Jumlah</span>
                    <span>Tanggal klaim</span>
                    <span>Status</span>
                    <span>Keterangan</span>
                </div>

                <ul class="divide-y divide-border" role="list">
                    @foreach ($claims as $claim)
                        @php
                            $post = $claim->post;
                            $statusKey = $normalizeStatus($claim->status);
                            $method = $methodLabel($claim->metode_pengambilan);
                        @endphp

                        <li class="grid gap-3 px-4 py-4 transition-colors hover:bg-muted/40 sm:px-6 lg:items-center lg:gap-4 {{ $columns }}">
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
                                    <p class="mt-0.5 flex items-center gap-1.5 truncate text-xs text-muted-foreground">
                                        <x-icon name="store" class="h-3 w-3 shrink-0" />
                                        <span class="truncate">{{ $post?->donor?->nama_usaha ?? 'Donatur FoodBridge' }}</span>
                                    </p>
                                </div>
                            </div>

                            {{-- Jumlah porsi --}}
                            <div class="flex items-center justify-between gap-3 lg:block">
                                <span class="text-xs text-muted-foreground lg:sr-only">Jumlah</span>
                                <p class="text-sm text-muted-foreground">
                                    <span class="font-semibold text-foreground">{{ $claim->jumlah_porsi }}</span> porsi
                                </p>
                            </div>

                            {{-- Tanggal klaim --}}
                            <div class="flex items-center justify-between gap-3 lg:block">
                                <span class="text-xs text-muted-foreground lg:sr-only">Tanggal klaim</span>
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

                            {{-- Keterangan sesuai status --}}
                            <div class="border-t border-border pt-3 text-xs leading-5 text-muted-foreground lg:border-t-0 lg:pt-0">
                                @if ($statusKey === 'menunggu')
                                    <p class="flex items-center gap-2">
                                        <x-icon name="clock" class="h-3.5 w-3.5 shrink-0" />
                                        Menunggu persetujuan donor.
                                    </p>
                                @elseif ($statusKey === 'disetujui')
                                    @if ($claim->kode_verifikasi)
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="inline-flex items-center gap-1.5 rounded-lg border border-border bg-background px-2.5 py-1">
                                                <x-icon name="shield-check" class="h-3.5 w-3.5 text-primary" />
                                                <span class="font-mono text-sm font-semibold tracking-[0.2em] text-foreground">{{ $claim->kode_verifikasi }}</span>
                                            </span>
                                            <span>Tunjukkan kode ini ke donor.</span>
                                        </div>
                                    @else
                                        <p class="flex items-center gap-2">
                                            <x-icon name="info" class="h-3.5 w-3.5 shrink-0" />
                                            Klaim disetujui. Metode pengambilan belum dipilih.
                                        </p>
                                    @endif

                                    @if ($method || $claim->approved_at)
                                        <p class="mt-1.5">
                                            @if ($method)
                                                {{ $method }}
                                            @endif
                                            @if ($method && $claim->approved_at)
                                                , disetujui
                                            @elseif ($claim->approved_at)
                                                Disetujui
                                            @endif
                                            @if ($claim->approved_at)
                                                {{ $claim->approved_at->format('d/m/Y H:i') }}
                                            @endif
                                        </p>
                                    @endif
                                @elseif ($statusKey === 'selesai')
                                    <p class="flex items-center gap-2">
                                        <x-icon name="check" class="h-3.5 w-3.5 shrink-0 text-[#166534]" />
                                        @if ($claim->completed_at)
                                            Makanan diserahkan pada {{ $claim->completed_at->format('d/m/Y H:i') }}.
                                        @else
                                            Makanan sudah diserahkan.
                                        @endif
                                    </p>
                                @elseif ($statusKey === 'ditolak')
                                    <p class="flex items-center gap-2">
                                        <x-icon name="x" class="h-3.5 w-3.5 shrink-0 text-[#B91C1C]" />
                                        Klaim tidak disetujui donor dan tidak dihitung ke kuota.
                                    </p>
                                @elseif ($statusKey === 'dibatalkan')
                                    <p class="flex items-center gap-2">
                                        <x-icon name="x" class="h-3.5 w-3.5 shrink-0" />
                                        Klaim dibatalkan.
                                    </p>
                                @else
                                    <p>-</p>
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