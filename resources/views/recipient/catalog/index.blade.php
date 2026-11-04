@php
    $hasActiveFilters = $search !== '' || $selectedCategoryId !== null || ($selectedRadiusKm !== null && $recipientLocation !== null);
    $selectedCategoryName = $categories->firstWhere('id', $selectedCategoryId)?->nama_kategori;
    $resetFiltersUrl = route('recipient.catalog.index');
@endphp

<x-recipient-catalog-layout
    title="Katalog Peta Pangan - FoodBridge"
    :recipient="$recipient"
    :daily-claimed-portions="$dailyClaimedPortions"
>
    <div class="mx-auto max-w-[1600px] lg:px-6 lg:py-6">
        <div class="lg:grid lg:h-[calc(100dvh-7rem)] lg:min-h-[44rem] lg:grid-cols-[minmax(0,27rem)_minmax(0,1fr)] lg:gap-6 xl:grid-cols-[minmax(0,30rem)_minmax(0,1fr)]">
            <aside class="flex min-h-0 flex-col border-b border-border bg-card lg:overflow-hidden lg:rounded-3xl lg:border lg:shadow-sm">
                <div class="border-b border-border p-4 sm:p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-primary">Penerima / Yayasan</p>
                            <h1 class="mt-1.5 text-2xl font-semibold tracking-tight text-foreground">Katalog Peta Pangan</h1>
                            <p class="mt-1.5 max-w-md text-sm leading-6 text-muted-foreground">
                                Temukan surplus makanan layak konsumsi di sekitar lembaga Anda.
                            </p>
                        </div>

                        <span class="hidden h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-accent text-primary sm:flex">
                            <x-icon name="utensils-crossed" class="h-5 w-5" />
                        </span>
                    </div>

                    <form method="GET" action="{{ route('recipient.catalog.index') }}" class="mt-5" x-data="{ filtersOpen: {{ $selectedCategoryId !== null || $selectedRadiusKm !== null ? 'true' : 'false' }} }">
                        <div class="relative">
                            <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <input
                                id="catalog-search"
                                name="q"
                                type="search"
                                value="{{ $search }}"
                                placeholder="Cari makanan, donatur, atau lokasi"
                                class="h-11 w-full rounded-xl border border-input bg-background pl-10 pr-20 text-sm text-foreground shadow-xs transition placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                            />
                            <button
                                type="submit"
                                class="absolute right-1.5 top-1/2 inline-flex h-8 -translate-y-1/2 items-center justify-center rounded-lg bg-primary px-3 text-xs font-semibold text-primary-foreground transition hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                            >
                                Cari
                            </button>
                        </div>

                        <div class="mt-3 flex items-center justify-between gap-3">
                            <p class="flex min-w-0 items-center gap-2 text-xs font-medium text-muted-foreground">
                                <span class="h-2 w-2 shrink-0 rounded-full bg-primary"></span>
                                <span class="truncate">
                                    {{ $availableCount }} donasi aktif
                                    {{ $recipientLocation !== null ? 'di sekitar Anda' : 'tersedia' }}
                                </span>
                            </p>

                            <button
                                type="button"
                                x-on:click="filtersOpen = ! filtersOpen"
                                x-bind:aria-expanded="filtersOpen"
                                aria-controls="catalog-filters"
                                @class([
                                    'inline-flex shrink-0 items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-medium transition',
                                    'border-primary/20 bg-accent text-primary' => $hasActiveFilters,
                                    'border-border bg-background text-foreground hover:bg-accent' => ! $hasActiveFilters,
                                ])
                            >
                                <x-icon name="filter" class="h-3.5 w-3.5" />
                                Filter
                                @if ($hasActiveFilters)
                                    <span class="flex h-4 min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-semibold text-primary-foreground">
                                        {{ ($selectedCategoryId !== null ? 1 : 0) + ($selectedRadiusKm !== null && $recipientLocation !== null ? 1 : 0) }}
                                    </span>
                                @endif
                            </button>
                        </div>

                        <div id="catalog-filters" x-cloak x-show="filtersOpen" class="mt-3 rounded-xl border border-border bg-background p-3">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="category_id" class="text-[11px] font-semibold uppercase tracking-[0.08em] text-muted-foreground">Kategori gizi</label>
                                    <select
                                        id="category_id"
                                        name="category_id"
                                        class="mt-1.5 h-10 w-full rounded-lg border border-input bg-card px-3 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    >
                                        <option value="">Semua kategori</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}" @selected($selectedCategoryId === $category->id)>
                                                {{ $category->nama_kategori }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="radius_km" class="text-[11px] font-semibold uppercase tracking-[0.08em] text-muted-foreground">Radius</label>
                                    <select
                                        id="radius_km"
                                        name="radius_km"
                                        class="mt-1.5 h-10 w-full rounded-lg border border-input bg-card px-3 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 disabled:cursor-not-allowed disabled:bg-muted disabled:text-muted-foreground"
                                        @disabled($recipientLocation === null)
                                    >
                                        <option value="">Semua jarak</option>
                                        @foreach ([5, 10, 25, 50, 100] as $radiusOption)
                                            <option value="{{ $radiusOption }}" @selected($selectedRadiusKm === $radiusOption)>
                                                {{ $radiusOption }} km
                                            </option>
                                        @endforeach
                                    </select>

                                    @if ($recipientLocation === null)
                                        <p class="mt-1.5 text-[11px] leading-4 text-muted-foreground">
                                            <a href="{{ route('profile.edit') }}" class="font-medium text-primary underline underline-offset-2">Atur lokasi lembaga</a>
                                            untuk memakai filter radius.
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <div class="mt-3 flex items-center justify-between gap-3">
                                @if ($hasActiveFilters)
                                    <a href="{{ $resetFiltersUrl }}" class="text-xs font-medium text-muted-foreground transition hover:text-foreground">Atur ulang</a>
                                @else
                                    <span class="text-[11px] text-muted-foreground">Filter diterapkan pada katalog dan peta.</span>
                                @endif

                                <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-primary px-3.5 py-2 text-xs font-semibold text-primary-foreground transition hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2">
                                    Terapkan
                                </button>
                            </div>
                        </div>

                        @if ($hasActiveFilters)
                            <div class="mt-3 flex flex-wrap items-center gap-1.5">
                                @if ($search !== '')
                                    <span class="inline-flex max-w-full items-center gap-1.5 rounded-full border border-border bg-muted px-2.5 py-1 text-[11px] font-medium text-foreground">
                                        <x-icon name="search" class="h-3 w-3 shrink-0 text-muted-foreground" />
                                        <span class="truncate">“{{ $search }}”</span>
                                    </span>
                                @endif

                                @if ($selectedCategoryName !== null)
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-border bg-muted px-2.5 py-1 text-[11px] font-medium text-foreground">
                                        <x-icon name="tags" class="h-3 w-3 text-muted-foreground" />
                                        {{ $selectedCategoryName }}
                                    </span>
                                @endif

                                @if ($selectedRadiusKm !== null && $recipientLocation !== null)
                                    <span class="inline-flex items-center gap-1.5 rounded-full border border-border bg-muted px-2.5 py-1 text-[11px] font-medium text-foreground">
                                        <x-icon name="map-pin" class="h-3 w-3 text-muted-foreground" />
                                        {{ $selectedRadiusKm }} km
                                    </span>
                                @endif

                                <a href="{{ $resetFiltersUrl }}" class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-[11px] font-medium text-muted-foreground transition hover:bg-muted hover:text-foreground">
                                    <x-icon name="x" class="h-3 w-3" />
                                    Hapus filter
                                </a>
                            </div>
                        @endif
                    </form>

                    @if ($recipientLocation === null)
                        <div class="mt-4 flex items-start gap-3 rounded-xl border border-secondary/25 bg-secondary/5 px-3.5 py-3 text-xs leading-5 text-foreground">
                            <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0 text-secondary" />
                            <p>
                                <span class="font-semibold">Lokasi lembaga belum diatur.</span>
                                Katalog sementara diurutkan berdasarkan batas konsumsi terdekat.
                                <a href="{{ route('profile.edit') }}" class="font-semibold text-primary underline underline-offset-2">Perbarui profil</a>
                                agar jarak dan peta dapat ditampilkan.
                            </p>
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-between gap-3 border-b border-border px-4 py-3 sm:px-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-muted-foreground">Makanan tersedia</p>
                        <p class="mt-0.5 text-xs text-muted-foreground">{{ $availableCount }} hasil ditemukan</p>
                    </div>

                    <span class="inline-flex items-center gap-1.5 rounded-full bg-accent px-2.5 py-1 text-[11px] font-medium text-primary">
                        <x-icon name="clock" class="h-3.5 w-3.5" />
                        Terdekat &amp; batas konsumsi
                    </span>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-3 sm:p-4 lg:overscroll-contain lg:pr-3 lg:[scrollbar-gutter:stable]">
                    @if ($foodPosts->isEmpty())
                        <div class="flex min-h-64 flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-background px-6 py-10 text-center">
                            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted text-muted-foreground">
                                <x-icon name="utensils-crossed" class="h-7 w-7" />
                            </span>
                            <h2 class="mt-4 text-sm font-semibold text-foreground">Belum ada makanan yang cocok</h2>
                            <p class="mt-1.5 max-w-xs text-xs leading-5 text-muted-foreground">
                                Coba kata kunci lain, perluas radius, atau hapus filter yang sedang aktif.
                            </p>
                            @if ($hasActiveFilters)
                                <a href="{{ $resetFiltersUrl }}" class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-primary px-3.5 py-2 text-xs font-semibold text-primary-foreground transition hover:bg-primary/90">
                                    <x-icon name="x" class="h-3.5 w-3.5" />
                                    Hapus semua filter
                                </a>
                            @endif
                        </div>
                    @else
                        @error('jumlah_porsi')
                            <div class="mb-3 flex items-start gap-3 rounded-xl border border-[#FCA5A5] bg-[#FEE2E2] px-3.5 py-3 text-xs font-medium leading-5 text-[#B91C1C]" role="alert">
                                <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0" />
                                <p>{{ $message }}</p>
                            </div>
                        @enderror

                        <div class="space-y-3">
                            @foreach ($foodPosts as $foodPost)
                                <article
                                    x-data="{ open: false, qty: 1, max: {{ (int) $foodPost->sisa_porsi }}, submitting: false }"
                                    class="group overflow-hidden rounded-2xl border border-border bg-card p-3 transition duration-200 hover:border-primary/25 hover:shadow-sm"
                                >
                                    <div class="flex gap-3">
                                        <div class="relative h-28 w-24 shrink-0 overflow-hidden rounded-xl bg-muted sm:h-32 sm:w-28">
                                            <x-icon name="leaf" class="absolute left-1/2 top-1/2 h-8 w-8 -translate-x-1/2 -translate-y-1/2 text-muted-foreground/35" />
                                            <img
                                                src="{{ asset('storage/'.$foodPost->foto) }}"
                                                alt="{{ $foodPost->judul }}"
                                                loading="lazy"
                                                class="relative h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                                                onerror="this.style.opacity='0'"
                                            />

                                            <span @class([
                                                'absolute left-1.5 top-1.5 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[9px] font-semibold uppercase tracking-[0.08em] shadow-sm',
                                                'bg-secondary text-white' => $foodPost->is_urgent,
                                                'bg-white/95 text-primary' => ! $foodPost->is_urgent,
                                            ])>
                                                <span @class([
                                                    'h-1.5 w-1.5 rounded-full',
                                                    'bg-white' => $foodPost->is_urgent,
                                                    'bg-primary' => ! $foodPost->is_urgent,
                                                ])></span>
                                                {{ $foodPost->is_urgent ? 'Mendesak' : 'Tersedia' }}
                                            </span>
                                        </div>

                                        <div class="flex min-w-0 flex-1 flex-col">
                                            <div class="flex items-start justify-between gap-2">
                                                <p class="truncate text-[10px] font-semibold uppercase tracking-[0.1em] text-muted-foreground">
                                                    {{ $foodPost->donor?->nama_usaha ?? 'Donatur FoodBridge' }}
                                                </p>

                                                @if ($foodPost->distance_km !== null)
                                                    <span class="inline-flex shrink-0 items-center gap-0.5 text-[11px] font-semibold text-primary">
                                                        <x-icon name="map-pin" class="h-3 w-3" />
                                                        {{ number_format($foodPost->distance_km, 1, ',', '.') }} km
                                                    </span>
                                                @endif
                                            </div>

                                            <h2 class="mt-1 line-clamp-2 text-sm font-semibold leading-5 text-foreground">{{ $foodPost->judul }}</h2>

                                            <p class="mt-1 truncate text-[11px] text-muted-foreground">
                                                {{ $foodPost->category?->nama_kategori ?? 'Kategori umum' }}
                                            </p>

                                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                                <span class="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 text-[10px] font-medium text-foreground">
                                                    <x-icon name="package" class="h-3 w-3 text-muted-foreground" />
                                                    {{ $foodPost->sisa_porsi }} porsi
                                                </span>

                                                <span @class([
                                                    'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold',
                                                    'bg-secondary/10 text-secondary' => $foodPost->is_urgent,
                                                    'bg-accent text-primary' => ! $foodPost->is_urgent,
                                                ]) title="Sisa waktu sebelum batas konsumsi">
                                                    <x-icon name="clock" class="h-3 w-3" />
                                                    {{ $foodPost->time_left_short }}
                                                </span>
                                            </div>

                                            <div class="mt-auto flex items-center justify-between gap-2 pt-3">
                                                <button
                                                    type="button"
                                                    data-focus-post="{{ $foodPost->id }}"
                                                    class="inline-flex items-center gap-1 rounded-lg px-1.5 py-1 text-[11px] font-medium text-muted-foreground transition hover:bg-muted hover:text-foreground focus:outline-none focus:ring-2 focus:ring-primary/20"
                                                >
                                                    <x-icon name="navigation" class="h-3 w-3" />
                                                    Lihat peta
                                                </button>

                                                <button
                                                    type="button"
                                                    x-on:click="open = ! open"
                                                    x-bind:aria-expanded="open"
                                                    aria-controls="claim-form-{{ $foodPost->id }}"
                                                    class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-primary px-3 py-1.5 text-[11px] font-semibold text-primary-foreground transition hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2"
                                                >
                                                    <x-icon name="clipboard-check" class="h-3 w-3" />
                                                    <span x-text="open ? 'Tutup' : 'Klaim Porsi'">Klaim Porsi</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <form
                                        id="claim-form-{{ $foodPost->id }}"
                                        method="POST"
                                        action="{{ route('recipient.claims.store', $foodPost) }}"
                                        x-cloak
                                        x-show="open"
                                        x-on:submit="submitting = true"
                                        class="mt-3 rounded-xl border border-border bg-background p-3"
                                    >
                                        @csrf

                                        <div class="flex items-center justify-between gap-3">
                                            <label for="jumlah_porsi_{{ $foodPost->id }}" class="text-[11px] font-semibold uppercase tracking-[0.08em] text-muted-foreground">
                                                Jumlah porsi
                                            </label>
                                            <span class="text-[11px] text-muted-foreground">Sisa {{ $foodPost->sisa_porsi }} porsi</span>
                                        </div>

                                        <div class="mt-2 flex items-center gap-2">
                                            <button
                                                type="button"
                                                x-on:click="qty = Math.max(1, qty - 1)"
                                                x-bind:disabled="qty <= 1"
                                                aria-label="Kurangi porsi"
                                                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-input bg-card text-foreground transition hover:bg-accent focus:outline-none focus:ring-2 focus:ring-primary/20 disabled:cursor-not-allowed disabled:text-muted-foreground"
                                            >
                                                <x-icon name="minus" class="h-4 w-4" />
                                            </button>

                                            <input
                                                type="number"
                                                name="jumlah_porsi"
                                                id="jumlah_porsi_{{ $foodPost->id }}"
                                                min="1"
                                                max="{{ $foodPost->sisa_porsi }}"
                                                x-model.number="qty"
                                                required
                                                class="h-10 w-full min-w-0 rounded-lg border border-input bg-card px-3 text-center text-sm font-semibold text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                            />

                                            <button
                                                type="button"
                                                x-on:click="qty = Math.min(max, qty + 1)"
                                                x-bind:disabled="qty >= max"
                                                aria-label="Tambah porsi"
                                                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-input bg-card text-foreground transition hover:bg-accent focus:outline-none focus:ring-2 focus:ring-primary/20 disabled:cursor-not-allowed disabled:text-muted-foreground"
                                            >
                                                <x-icon name="plus" class="h-4 w-4" />
                                            </button>
                                        </div>

                                        <div class="mt-3 flex items-center justify-between gap-3">
                                            <p class="text-[11px] leading-4 text-muted-foreground">Klaim menunggu persetujuan donor.</p>

                                            <button
                                                type="submit"
                                                x-bind:disabled="submitting"
                                                class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-lg bg-primary px-3.5 py-2 text-xs font-semibold text-primary-foreground transition hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                Ajukan Klaim
                                            </button>
                                        </div>
                                    </form>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($foodPosts->hasPages())
                    <div class="border-t border-border px-4 py-3 sm:px-5">
                        {{ $foodPosts->onEachSide(1)->links() }}
                    </div>
                @endif
            </aside>

            <section class="relative min-h-[30rem] overflow-hidden border-b border-border bg-[#E8EEE8] lg:min-h-0 lg:rounded-3xl lg:border lg:border-border">
                <div id="food-catalog-map" class="absolute inset-0 z-0"></div>

                <div class="pointer-events-none absolute inset-x-4 top-4 z-10 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div class="pointer-events-auto max-w-sm rounded-2xl border border-border/80 bg-white/95 px-4 py-3 shadow-sm backdrop-blur">
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                                <x-icon name="map-pin" class="h-3.5 w-3.5" />
                            </span>
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">Peta pangan</p>
                                <p class="text-sm font-semibold text-foreground">{{ $mapPosts->count() }} titik makanan</p>
                            </div>
                        </div>
                        <p class="mt-2 text-[11px] leading-4 text-muted-foreground">
                            Klik penanda untuk melihat detail singkat. Tombol “Lihat peta” pada kartu akan memusatkan peta ke lokasi makanan.
                        </p>
                    </div>

                    <div class="pointer-events-auto w-fit rounded-2xl border border-border/80 bg-white/95 px-3.5 py-3 text-[11px] shadow-sm backdrop-blur">
                        <p class="mb-2 text-[9px] font-semibold uppercase tracking-[0.12em] text-muted-foreground">Legenda</p>
                        <div class="space-y-1.5">
                            <p class="flex items-center gap-2 text-foreground">
                                <span class="h-2.5 w-2.5 rounded-full bg-primary"></span>
                                Aman dikonsumsi
                            </p>
                            <p class="flex items-center gap-2 text-foreground">
                                <span class="h-2.5 w-2.5 rounded-full bg-secondary"></span>
                                Mendesak
                            </p>
                            @if ($recipientLocation !== null)
                                <p class="flex items-center gap-2 text-foreground">
                                    <span class="h-2.5 w-2.5 rounded-full border-2 border-primary bg-white"></span>
                                    Lokasi lembaga
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($effectiveRadiusKm !== null)
                    <div class="pointer-events-none absolute bottom-4 left-4 z-10">
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-border/80 bg-white/95 px-3 py-1.5 text-[11px] font-medium text-foreground shadow-sm backdrop-blur">
                            <x-icon name="navigation" class="h-3.5 w-3.5 text-primary" />
                            Radius {{ $effectiveRadiusKm }} km
                        </span>
                    </div>
                @endif

                @if ($mapPosts->isEmpty())
                    <div class="pointer-events-none absolute inset-x-4 bottom-4 z-10 flex justify-center">
                        <div class="rounded-xl border border-border/80 bg-white/95 px-4 py-2.5 text-center text-xs text-muted-foreground shadow-sm backdrop-blur">
                            Tidak ada titik makanan pada filter ini.
                        </div>
                    </div>
                @endif

                <div id="food-catalog-map-loading" class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-3 bg-[#E8EEE8]/80 backdrop-blur-sm">
                    <span class="h-8 w-8 animate-spin rounded-full border-2 border-primary/20 border-t-primary" aria-hidden="true"></span>
                    <p class="text-xs font-medium text-muted-foreground">Memuat peta pangan…</p>
                </div>

                <div id="food-catalog-map-fallback" class="absolute inset-0 z-20 hidden flex-col items-center justify-center gap-3 bg-[#E8EEE8] px-6 text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-muted-foreground shadow-sm">
                        <x-icon name="map-pin" class="h-6 w-6" />
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-foreground">Peta belum dapat dimuat</p>
                        <p class="mt-1 max-w-xs text-xs leading-5 text-muted-foreground">
                            Periksa koneksi internet Anda. Daftar makanan di samping tetap dapat digunakan.
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <style>
        .fb-map-marker {
            display: flex;
            width: 2.25rem;
            height: 2.25rem;
            align-items: center;
            justify-content: center;
            border: 3px solid #ffffff;
            border-radius: 9999px;
            background: #133830;
            color: #ffffff;
            font-size: 0.68rem;
            font-weight: 700;
            box-shadow: 0 8px 22px rgba(17, 24, 22, 0.22);
        }

        .fb-map-marker--urgent {
            background: #D97736;
        }

        .fb-map-marker--recipient {
            width: 2.6rem;
            height: 2.6rem;
            border-color: #133830;
            background: #ffffff;
            color: #133830;
            font-size: 0.58rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .fb-map-popup {
            min-width: 13rem;
            font-family: inherit;
            color: #111816;
        }

        .fb-map-popup__eyebrow {
            margin: 0 0 0.25rem;
            color: #6B746E;
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .fb-map-popup__title {
            margin: 0;
            font-size: 0.84rem;
            font-weight: 700;
            line-height: 1.25rem;
        }

        .fb-map-popup__meta {
            margin: 0.35rem 0 0;
            color: #6B746E;
            font-size: 0.72rem;
            line-height: 1rem;
        }

        .fb-map-popup__stats {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            margin-top: 0.55rem;
        }

        .fb-map-popup__stat {
            border-radius: 9999px;
            background: #EFF1ED;
            padding: 0.18rem 0.5rem;
            color: #133830;
            font-size: 0.66rem;
            font-weight: 600;
        }

        .fb-map-popup__stat--urgent {
            background: rgba(217, 119, 54, 0.12);
            color: #B95F22;
        }

        .leaflet-popup-content-wrapper {
            border-radius: 0.9rem;
            box-shadow: 0 12px 30px rgba(17, 24, 22, 0.18);
        }

        .leaflet-popup-content {
            margin: 0.9rem 1rem;
        }

        .leaflet-container {
            font-family: inherit;
        }
    </style>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const loading = document.getElementById('food-catalog-map-loading');
            const fallback = document.getElementById('food-catalog-map-fallback');

            if (typeof L === 'undefined') {
                loading?.classList.add('hidden');
                fallback?.classList.remove('hidden');
                fallback?.classList.add('flex');

                return;
            }

            const mapPosts = {{ Illuminate\Support\Js::from($mapPosts) }};
            const recipientLocation = {{ Illuminate\Support\Js::from($recipientLocation) }};
            const radiusKm = {{ Illuminate\Support\Js::from($effectiveRadiusKm) }};
            const defaultCenter = [-2.5489, 118.0149];
            const map = L.map('food-catalog-map', {
                scrollWheelZoom: false,
                zoomControl: false,
            }).setView(defaultCenter, 5);

            L.control.zoom({ position: 'bottomright' }).addTo(map);

            const tiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19,
            }).addTo(map);

            tiles.on('tileerror', function () {
                fallback?.classList.remove('hidden');
                fallback?.classList.add('flex');
            });

            const bounds = [];
            const markersById = new Map();

            function appendStat(container, text, urgent) {
                const stat = document.createElement('span');
                stat.className = 'fb-map-popup__stat' + (urgent ? ' fb-map-popup__stat--urgent' : '');
                stat.textContent = text;
                container.append(stat);
            }

            function createPopup(post) {
                const container = document.createElement('div');
                const eyebrow = document.createElement('p');
                const title = document.createElement('p');
                const meta = document.createElement('p');
                const stats = document.createElement('div');

                container.className = 'fb-map-popup';
                eyebrow.className = 'fb-map-popup__eyebrow';
                title.className = 'fb-map-popup__title';
                meta.className = 'fb-map-popup__meta';
                stats.className = 'fb-map-popup__stats';

                eyebrow.textContent = post.category || 'Kategori umum';
                title.textContent = post.judul;
                meta.textContent = (post.donor || 'Donatur FoodBridge') + ' · ' + post.alamat;

                appendStat(stats, post.porsi + ' porsi', false);
                appendStat(stats, 'Sisa ' + post.time_left, Boolean(post.is_urgent));

                if (post.distance_km !== null) {
                    appendStat(
                        stats,
                        Number(post.distance_km).toFixed(1).replace('.', ',') + ' km',
                        false,
                    );
                }

                container.append(eyebrow, title, meta, stats);

                return container;
            }

            if (recipientLocation !== null) {
                const recipientMarker = L.marker(recipientLocation, {
                    icon: L.divIcon({
                        className: '',
                        html: '<span class="fb-map-marker fb-map-marker--recipient">Anda</span>',
                        iconSize: [42, 42],
                        iconAnchor: [21, 21],
                    }),
                    zIndexOffset: 1000,
                }).addTo(map);

                recipientMarker.bindPopup('Lokasi lembaga Anda');
                bounds.push(recipientLocation);

                if (radiusKm !== null) {
                    L.circle(recipientLocation, {
                        radius: radiusKm * 1000,
                        color: '#133830',
                        fillColor: '#133830',
                        fillOpacity: 0.035,
                        weight: 1,
                        dashArray: '5 6',
                    }).addTo(map);
                }
            }

            mapPosts.forEach(function (post) {
                const marker = L.marker([post.lat, post.lng], {
                    icon: L.divIcon({
                        className: '',
                        html: '<span class="fb-map-marker' + (post.is_urgent ? ' fb-map-marker--urgent' : '') + '">' + post.porsi + '</span>',
                        iconSize: [36, 36],
                        iconAnchor: [18, 18],
                        popupAnchor: [0, -18],
                    }),
                }).addTo(map);

                marker.bindPopup(createPopup(post));
                markersById.set(String(post.id), marker);
                bounds.push([post.lat, post.lng]);
            });

            function focusPost(postId) {
                const marker = markersById.get(String(postId));

                if (! marker) {
                    return;
                }

                const position = marker.getLatLng();
                map.flyTo(position, 15, { animate: true, duration: 0.55 });
                marker.openPopup();

                if (window.matchMedia('(max-width: 1023px)').matches) {
                    document.getElementById('food-catalog-map')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }

            document.querySelectorAll('[data-focus-post]').forEach(function (button) {
                button.addEventListener('click', function () {
                    focusPost(button.dataset.focusPost);
                });
            });

            if (bounds.length > 1) {
                map.fitBounds(bounds, { padding: [42, 42], maxZoom: 14 });
            } else if (bounds.length === 1) {
                map.setView(bounds[0], 14);
            }

            map.whenReady(function () {
                loading?.classList.add('hidden');
            });

            window.setTimeout(function () {
                map.invalidateSize();
            }, 120);
        });
    </script>
</x-recipient-catalog-layout>