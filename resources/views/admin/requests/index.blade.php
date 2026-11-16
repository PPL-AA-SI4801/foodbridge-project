@php
    $summary = [];
    foreach (collect($statusCounts ?? []) as $status => $total) {
        $summary[(string) $status] = (int) $total;
    }

    $stats = [
        'open' => ['label' => 'Terbuka', 'hint' => 'Menunggu donatur', 'icon' => 'message-square'],
        'in_progress' => ['label' => 'Sedang Diproses', 'hint' => 'Donatur berkomitmen', 'icon' => 'clock'],
        'fulfilled' => ['label' => 'Terpenuhi', 'hint' => 'Kebutuhan terpenuhi', 'icon' => 'check'],
        'expired' => ['label' => 'Kedaluwarsa', 'hint' => 'Melewati batas waktu', 'icon' => 'info'],
        'cancelled' => ['label' => 'Dibatalkan', 'hint' => 'Ditutup / dibatalkan', 'icon' => 'x'],
    ];

    $inputClasses = 'block w-full rounded-lg border border-input bg-white px-3 py-2 text-sm text-foreground shadow-xs transition placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20';
@endphp

<x-app-layout padded title="Request Board" :pending-verifications="$pendingVerifications">
    <x-flash />

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Request Board</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Pantau dan moderasi seluruh permintaan kebutuhan makanan yang diajukan oleh lembaga penerima manfaat.
            </p>
        </div>

        <span class="inline-flex w-fit items-center gap-2 rounded-full border border-border bg-card px-3.5 py-1.5 text-sm text-muted-foreground">
            <span class="font-semibold text-foreground">{{ $summary['open'] ?? 0 }}</span>
            permintaan terbuka aktif
        </span>
    </div>

    {{-- Ringkasan status --}}
    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        @foreach ($stats as $key => $stat)
            @php
                $isActive = $activeStatus === $key;
            @endphp
            <a
                href="{{ route('admin.requests.index', $isActive ? array_filter(['q' => $search, 'urgency' => $activeUrgency, 'category_id' => $selectedCategoryId]) : array_filter(['status' => $key, 'q' => $search, 'urgency' => $activeUrgency, 'category_id' => $selectedCategoryId])) }}"
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
                <p class="mt-3 text-2xl font-semibold text-foreground">{{ number_format($summary[$key] ?? 0, 0, ',', '.') }}</p>
                <p class="mt-0.5 text-xs text-muted-foreground">{{ $stat['hint'] }}</p>
            </a>
        @endforeach
    </div>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('admin.requests.index') }}" class="mb-6 rounded-xl border border-border bg-card p-4">
        @if ($activeStatus)
            <input type="hidden" name="status" value="{{ $activeStatus }}" />
        @endif
        <div class="grid gap-3 sm:grid-cols-4">
            <div class="sm:col-span-2">
                <label for="q" class="sr-only">Cari permintaan</label>
                <input
                    id="q"
                    name="q"
                    type="search"
                    value="{{ $search }}"
                    placeholder="Cari nama makanan, lembaga penerima, atau donatur..."
                    class="{{ $inputClasses }}"
                />
            </div>

            <div>
                <label for="category_id" class="sr-only">Kategori</label>
                <select id="category_id" name="category_id" class="{{ $inputClasses }}">
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected($selectedCategoryId === $category->id)>
                            {{ $category->nama_kategori }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <select id="urgency" name="urgency" class="{{ $inputClasses }} flex-1">
                    <option value="">Semua Urgensi</option>
                    @foreach (\App\Enums\UrgencyLevel::cases() as $level)
                        <option value="{{ $level->value }}" @selected($activeUrgency === $level->value)>
                            {{ $level->badgeLabel() }}
                        </option>
                    @endforeach
                </select>

                <button
                    type="submit"
                    class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-lg bg-primary px-3.5 py-2 text-xs font-semibold text-primary-foreground transition hover:bg-primary/90"
                >
                    <x-icon name="search" class="h-4 w-4" />
                    Cari
                </button>
            </div>
        </div>
    </form>

    @if ($requests->isEmpty())
        <div class="rounded-lg border border-border bg-card p-10 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                <x-icon name="message-square" class="h-6 w-6" />
            </div>
            <h2 class="mt-4 text-base font-semibold">Tidak ada permintaan kebutuhan ditemukan</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Belum ada permintaan yang diajukan atau cocok dengan filter yang dipilih.
            </p>
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-border bg-card">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border text-sm">
                    <caption class="sr-only">Daftar seluruh permintaan kebutuhan makanan</caption>

                    <thead class="bg-muted text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th scope="col" class="px-6 py-3">Kebutuhan Makanan</th>
                            <th scope="col" class="px-6 py-3">Penerima</th>
                            <th scope="col" class="px-6 py-3">Urgensi &amp; Porsi</th>
                            <th scope="col" class="px-6 py-3">Batas Waktu</th>
                            <th scope="col" class="px-6 py-3">Donatur Penyalur</th>
                            <th scope="col" class="px-6 py-3">Status</th>
                            <th scope="col" class="px-6 py-3 text-right">Moderasi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-border">
                        @foreach ($requests as $req)
                            @php
                                $urgency = $req->urgency_level instanceof \App\Enums\UrgencyLevel
                                    ? $req->urgency_level
                                    : \App\Enums\UrgencyLevel::tryFrom((string) $req->urgency_level) ?? \App\Enums\UrgencyLevel::Mendesak;
                                $statusEnum = $req->status instanceof \App\Enums\FoodRequestStatus
                                    ? $req->status
                                    : \App\Enums\FoodRequestStatus::tryFrom((string) $req->status) ?? \App\Enums\FoodRequestStatus::Open;
                            @endphp

                            <tr class="transition-colors hover:bg-muted/50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-foreground">{{ $req->judul }}</div>
                                    @if ($req->deskripsi)
                                        <div class="mt-0.5 max-w-xs truncate text-xs text-muted-foreground">{{ $req->deskripsi }}</div>
                                    @endif
                                    <div class="mt-1 text-xs text-muted-foreground">
                                        {{ $req->category?->nama_kategori ?? 'Tanpa kategori' }} · {{ $req->metodePenerimaanLabel() }}
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="font-medium text-foreground">{{ $req->recipient?->nama_lembaga ?? '—' }}</div>
                                    <div class="mt-0.5 text-xs text-muted-foreground">{{ $req->recipient?->jenis_lembaga }}</div>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $urgency->badgeClass() }}">
                                        {{ $urgency->badgeLabel() }}
                                    </span>
                                    <div class="mt-1 text-xs font-medium text-foreground">{{ $req->jumlah_porsi }} porsi</div>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-xs text-muted-foreground">
                                    <div>{{ $req->batas_waktu->format('d/m/Y H:i') }}</div>
                                    <div class="mt-0.5">{{ $req->batas_waktu->locale('id')->diffForHumans() }}</div>
                                </td>

                                <td class="px-6 py-4 text-xs">
                                    @if ($req->donor)
                                        <div class="font-medium text-foreground">{{ $req->donor->nama_usaha }}</div>
                                        @if ($req->claim)
                                            <div class="mt-0.5 text-muted-foreground">
                                                Klaim #{{ $req->claim->id }} ({{ $req->claim->jumlah_porsi }} porsi)
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-muted-foreground">Belum ada</span>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">
                                    <x-status-badge :status="$statusEnum->value" />
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if (in_array($statusEnum, [\App\Enums\FoodRequestStatus::Open, \App\Enums\FoodRequestStatus::InProgress], true))
                                            <form
                                                method="POST"
                                                action="{{ route('admin.requests.cancel', $req) }}"
                                                onsubmit="return confirm('Tutup permintaan kebutuhan ini?');"
                                            >
                                                @csrf
                                                @method('PATCH')
                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center rounded-md border border-border bg-background px-2.5 py-1.5 text-xs font-medium text-foreground transition hover:bg-accent"
                                                >
                                                    Tutup
                                                </button>
                                            </form>
                                        @endif

                                        <form
                                            method="POST"
                                            action="{{ route('admin.requests.destroy', $req) }}"
                                            onsubmit="return confirm('Hapus permintaan kebutuhan ini dari sistem?');"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center rounded-md border border-[#FCA5A5] bg-card px-2.5 py-1.5 text-xs font-medium text-[#B91C1C] transition hover:bg-[#FEE2E2]"
                                            >
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($requests->hasPages())
                <div class="border-t border-border px-6 py-4">
                    {{ $requests->onEachSide(1)->links() }}
                </div>
            @endif
        </div>
    @endif
</x-app-layout>
