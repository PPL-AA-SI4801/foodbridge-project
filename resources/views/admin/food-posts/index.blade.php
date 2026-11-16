@php
    $summary = [];
    foreach (collect($statusCounts ?? []) as $status => $total) {
        $summary[(string) $status] = (int) $total;
    }

    $stats = [
        'tersedia' => ['label' => 'Tersedia', 'hint' => 'Masih dapat diklaim', 'icon' => 'package'],
        'habis' => ['label' => 'Habis', 'hint' => 'Seluruh porsi diklaim', 'icon' => 'check'],
        'kedaluwarsa' => ['label' => 'Kedaluwarsa', 'hint' => 'Lewat batas konsumsi', 'icon' => 'clock'],
    ];

    $inputClasses = 'block w-full rounded-lg border border-input bg-white px-3 py-2 text-sm text-foreground shadow-xs transition placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20';
@endphp

<x-app-layout padded title="Postingan Makanan" :pending-verifications="$pendingVerifications">
    <x-flash />

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Manajemen Postingan Makanan</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Kelola, buat, ubah, atau hapus postingan makanan dari seluruh donatur di platform.
            </p>
        </div>

        <a
            href="{{ route('admin.food-posts.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/30"
        >
            <x-icon name="plus" class="h-4 w-4" />
            Buat Postingan Makanan
        </a>
    </div>

    {{-- Ringkasan status --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        @foreach ($stats as $key => $stat)
            @php
                $isActive = $activeStatus === $key;
            @endphp
            <a
                href="{{ route('admin.food-posts.index', $isActive ? array_filter(['q' => $search, 'category_id' => $selectedCategoryId, 'donor_id' => $selectedDonorId]) : array_filter(['status' => $key, 'q' => $search, 'category_id' => $selectedCategoryId, 'donor_id' => $selectedDonorId])) }}"
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
    <form method="GET" action="{{ route('admin.food-posts.index') }}" class="mb-6 rounded-xl border border-border bg-card p-4">
        @if ($activeStatus)
            <input type="hidden" name="status" value="{{ $activeStatus }}" />
        @endif
        <div class="grid gap-3 sm:grid-cols-4">
            <div class="sm:col-span-2">
                <label for="q" class="sr-only">Cari postingan</label>
                <input
                    id="q"
                    name="q"
                    type="search"
                    value="{{ $search }}"
                    placeholder="Cari nama makanan, deskripsi, alamat, atau nama usaha donatur..."
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
                <select id="donor_id" name="donor_id" class="{{ $inputClasses }} flex-1">
                    <option value="">Semua Donatur</option>
                    @foreach ($donors as $donor)
                        <option value="{{ $donor->id }}" @selected($selectedDonorId === $donor->id)>
                            {{ $donor->nama_usaha }}
                        </option>
                    @endforeach
                </select>

                <button
                    type="submit"
                    class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-lg bg-primary px-3.5 py-2 text-xs font-semibold text-primary-foreground transition hover:bg-primary/90"
                >
                    <x-icon name="search" class="h-4 w-4" />
                    Filter
                </button>
            </div>
        </div>
    </form>

    @if ($foodPosts->isEmpty())
        <div class="rounded-lg border border-border bg-card p-10 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                <x-icon name="utensils-crossed" class="h-6 w-6" />
            </div>
            <h2 class="mt-4 text-base font-semibold">Belum ada postingan makanan</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Tidak ada postingan makanan yang ditemukan atau sesuai dengan filter pencarian.
            </p>
            <a
                href="{{ route('admin.food-posts.create') }}"
                class="mt-5 inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90"
            >
                <x-icon name="plus" class="h-4 w-4" />
                Buat Postingan Makanan
            </a>
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-border bg-card">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border text-sm">
                    <caption class="sr-only">Daftar seluruh postingan makanan</caption>

                    <thead class="bg-muted text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th scope="col" class="px-6 py-3">Makanan</th>
                            <th scope="col" class="px-6 py-3">Donatur</th>
                            <th scope="col" class="px-6 py-3">Kategori</th>
                            <th scope="col" class="px-6 py-3">Porsi</th>
                            <th scope="col" class="px-6 py-3">Batas Konsumsi</th>
                            <th scope="col" class="px-6 py-3">Status</th>
                            <th scope="col" class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-border">
                        @foreach ($foodPosts as $foodPost)
                            <tr class="transition-colors hover:bg-muted/50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="relative h-12 w-12 shrink-0 overflow-hidden rounded-md bg-muted">
                                            <img
                                                src="{{ asset('storage/'.$foodPost->foto) }}"
                                                alt="{{ $foodPost->judul }}"
                                                class="h-full w-full object-cover"
                                                onerror="this.style.opacity='0'"
                                            />
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-medium text-foreground">{{ $foodPost->judul }}</div>
                                            <div class="mt-0.5 max-w-xs truncate text-xs text-muted-foreground">{{ $foodPost->alamat }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="font-medium text-foreground">{{ $foodPost->donor?->nama_usaha ?? '—' }}</div>
                                    <div class="mt-0.5 text-xs text-muted-foreground">{{ $foodPost->donor?->jenis_usaha }}</div>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-muted-foreground">
                                    {{ $foodPost->category?->nama_kategori ?? '—' }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-muted-foreground">
                                    <span class="font-semibold text-foreground">{{ $foodPost->sisa_porsi }}</span>
                                    dari {{ $foodPost->jumlah_porsi }}
                                    @if ($foodPost->claims_count > 0)
                                        <div class="mt-0.5 text-xs text-muted-foreground">{{ $foodPost->claims_count }} klaim</div>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-xs text-muted-foreground">
                                    {{ $foodPost->batas_konsumsi?->format('d/m/Y H:i') }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">
                                    <x-status-badge :status="$foodPost->status" />
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a
                                            href="{{ route('admin.food-posts.edit', $foodPost) }}"
                                            class="font-medium text-primary hover:text-primary/80"
                                        >
                                            Ubah
                                        </a>

                                        <form
                                            method="POST"
                                            action="{{ route('admin.food-posts.destroy', $foodPost) }}"
                                            onsubmit="return confirm('Hapus postingan makanan ini?');"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="font-medium text-destructive hover:text-destructive/80">
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

            @if ($foodPosts->hasPages())
                <div class="border-t border-border px-6 py-4">
                    {{ $foodPosts->onEachSide(1)->links() }}
                </div>
            @endif
        </div>
    @endif
</x-app-layout>
