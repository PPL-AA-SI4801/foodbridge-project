@php
    $inputClasses = 'block w-full rounded-lg border border-input bg-white px-3 py-2 text-sm text-foreground shadow-xs transition placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20';

    $stats = [
        'menunggu' => ['label' => 'Menunggu', 'hint' => 'Perlu keputusan donor', 'icon' => 'clock'],
        'disetujui' => ['label' => 'Disetujui', 'hint' => 'Porsi sudah dikurangi', 'icon' => 'check'],
        'selesai' => ['label' => 'Selesai', 'hint' => 'Riwayat serah terima', 'icon' => 'package'],
        'ditolak' => ['label' => 'Ditolak', 'hint' => 'Tidak memakai porsi', 'icon' => 'x'],
    ];

    $metodeLabel = fn ($metode) => match ($metode) {
        'ambil_sendiri' => 'Ambil Sendiri',
        'kurir_online' => 'Kurir Online',
        null, '' => '—',
        default => ucfirst(str_replace('_', ' ', (string) $metode)),
    };
@endphp

<x-app-layout padded title="Manajemen Klaim" :pending-verifications="$pendingVerifications">
    <x-flash />

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Manajemen Klaim</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Pantau, buat, ubah, atau hapus klaim makanan dari seluruh penerima di platform.
            </p>
        </div>

        <a
            href="{{ route('admin.claims.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/30"
        >
            <x-icon name="plus" class="h-4 w-4" />
            Buat Klaim
        </a>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($stats as $key => $stat)
            @php
                $isActive = $activeStatus === $key;
            @endphp
            <a
                href="{{ route('admin.claims.index', $isActive ? array_filter(['q' => $search]) : array_filter(['status' => $key, 'q' => $search])) }}"
                @class([
                    'group rounded-xl border bg-card p-4 transition focus:outline-none focus:ring-2 focus:ring-primary/30',
                    'border-primary ring-2 ring-primary/15' => $isActive,
                    'border-border hover:border-primary/30' => ! $isActive,
                ])
            >
                <div class="flex items-start justify-between gap-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">{{ $stat['label'] }}</p>
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-border bg-muted text-muted-foreground">
                        <x-icon :name="$stat['icon']" class="h-4 w-4" />
                    </span>
                </div>
                <p class="mt-3 text-2xl font-semibold text-foreground">{{ number_format($statusCounts[$key] ?? 0, 0, ',', '.') }}</p>
                <p class="mt-0.5 text-xs text-muted-foreground">{{ $stat['hint'] }}</p>
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.claims.index') }}" class="mb-6 rounded-xl border border-border bg-card p-4">
        @if ($activeStatus)
            <input type="hidden" name="status" value="{{ $activeStatus }}" />
        @endif

        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="q" class="sr-only">Cari klaim</label>
                <input
                    id="q"
                    name="q"
                    type="search"
                    value="{{ $search }}"
                    placeholder="Cari makanan, donor, penerima, atau lembaga..."
                    class="{{ $inputClasses }}"
                />
            </div>

            <div class="sm:w-52">
                <label for="status" class="sr-only">Status klaim</label>
                <select id="status" name="status" class="{{ $inputClasses }}">
                    <option value="">Semua status</option>
                    @foreach (\App\Support\ClaimStatus::LABELS as $key => $label)
                        <option value="{{ $key }}" @selected($activeStatus === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button
                    type="submit"
                    class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90"
                >
                    <x-icon name="search" class="h-4 w-4" />
                    Filter
                </button>

                @if ($search !== '' || $activeStatus !== null)
                    <a
                        href="{{ route('admin.claims.index') }}"
                        class="inline-flex items-center justify-center rounded-lg border border-input bg-card px-4 py-2 text-sm font-medium transition-colors hover:bg-accent"
                    >
                        Reset
                    </a>
                @endif
            </div>
        </div>
    </form>

    @if ($claims->isEmpty())
        <div class="rounded-lg border border-border bg-card p-10 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                <x-icon name="clipboard-list" class="h-6 w-6" />
            </div>
            <h2 class="mt-4 text-base font-semibold">Belum ada klaim</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Tidak ada klaim yang ditemukan atau sesuai dengan filter pencarian.
            </p>
            <a
                href="{{ route('admin.claims.create') }}"
                class="mt-5 inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90"
            >
                <x-icon name="plus" class="h-4 w-4" />
                Buat Klaim
            </a>
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-border bg-card">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border text-sm">
                    <caption class="sr-only">Daftar seluruh klaim makanan</caption>

                    <thead class="bg-muted text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th scope="col" class="px-6 py-3">Makanan</th>
                            <th scope="col" class="px-6 py-3">Donor</th>
                            <th scope="col" class="px-6 py-3">Penerima</th>
                            <th scope="col" class="px-6 py-3">Porsi</th>
                            <th scope="col" class="px-6 py-3">Metode</th>
                            <th scope="col" class="px-6 py-3">Status</th>
                            <th scope="col" class="px-6 py-3">Tanggal</th>
                            <th scope="col" class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-border">
                        @foreach ($claims as $claim)
                            @php
                                $normalizedStatus = \App\Support\ClaimStatus::normalize($claim->status);
                                $isPending = in_array($claim->status, \App\Support\ClaimStatus::GROUPS['menunggu'], true);
                                $isCompleted = in_array($claim->status, \App\Support\ClaimStatus::GROUPS['selesai'], true);
                            @endphp

                            <tr class="transition-colors hover:bg-muted/50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-foreground">{{ $claim->post?->judul ?? '—' }}</div>
                                    <div class="mt-0.5 max-w-xs truncate text-xs text-muted-foreground">{{ $claim->post?->alamat }}</div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="font-medium text-foreground">{{ $claim->post?->donor?->nama_usaha ?? '—' }}</div>
                                    <div class="mt-0.5 text-xs text-muted-foreground">{{ $claim->post?->donor?->jenis_usaha }}</div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="font-medium text-foreground">{{ $claim->recipient?->nama_lembaga ?? '—' }}</div>
                                    <div class="mt-0.5 text-xs text-muted-foreground">{{ $claim->recipient?->user?->nama }}</div>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 font-semibold text-foreground">
                                    {{ $claim->jumlah_porsi }} porsi
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-muted-foreground">
                                    {{ $metodeLabel($claim->metode_pengambilan) }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">
                                    <x-status-badge :status="$normalizedStatus" />
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-xs text-muted-foreground">
                                    {{ $claim->tanggal_klaim?->format('d/m/Y') ?? '—' }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <div class="flex flex-wrap items-center justify-end gap-3">
                                        @if ($isPending)
                                            <a
                                                href="{{ route('admin.claims.edit', $claim) }}"
                                                class="font-medium text-primary hover:text-primary/80"
                                            >
                                                Ubah
                                            </a>
                                        @endif

                                        @if (! $isCompleted)
                                            <form
                                                method="POST"
                                                action="{{ route('admin.claims.destroy', $claim) }}"
                                                onsubmit="return confirm('Hapus klaim ini? Sisa porsi akan dikembalikan bila klaim sudah disetujui.');"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" dusk="delete-claim-{{ $claim->id }}" class="font-medium text-destructive hover:text-destructive/80">
                                                    Hapus
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-muted-foreground">Riwayat</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($claims->hasPages())
                <div class="border-t border-border px-6 py-4">
                    {{ $claims->onEachSide(1)->links() }}
                </div>
            @endif
        </div>
    @endif
</x-app-layout>
