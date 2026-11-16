<x-donor-form-layout title="Posting Surplus Makanan - FoodBridge">
    <section class="relative overflow-hidden border-b border-border bg-[#FCFCFB]">
        <div class="pointer-events-none absolute -right-24 -top-32 h-72 w-72 rounded-full bg-accent/80 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-28 left-1/3 h-64 w-64 rounded-full bg-secondary/10 blur-3xl" aria-hidden="true"></div>

        <div class="relative mx-auto max-w-7xl px-4 py-9 sm:px-6 sm:py-11 lg:px-8">
            <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <div class="max-w-3xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary">Portal Donatur</p>
                    <h1 class="mt-3 text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
                        Posting Surplus Makanan
                    </h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-muted-foreground sm:text-base">
                        Bantu makanan layak konsumsi sampai ke lembaga sosial di sekitar Anda. Lengkapi detail makanan,
                        tentukan batas aman konsumsi, dan pastikan titik jemput mudah ditemukan.
                    </p>
                </div>

                <div class="rounded-2xl border border-border bg-card p-5">
                    <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em] text-primary">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-accent">
                            <x-icon name="shield-check" class="h-4 w-4" />
                        </span>
                        Donatur Terverifikasi
                    </div>
                    <p class="mt-4 truncate text-base font-semibold text-foreground">{{ $donor->nama_usaha }}</p>
                    <p class="mt-1 text-sm text-muted-foreground">{{ $donor->jenis_usaha }}</p>
                    <div class="mt-4 flex items-start gap-2 border-t border-border pt-4 text-sm text-muted-foreground">
                        <x-icon name="map-pin" class="mt-0.5 h-4 w-4 shrink-0 text-secondary" />
                        <span class="line-clamp-2">{{ $donor->alamat }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-8 flex flex-col gap-4 rounded-2xl border border-primary/15 bg-primary px-5 py-4 text-primary-foreground sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10">
                        <x-icon name="shield-check" class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-sm font-semibold sm:text-base">Protokol Keamanan Otomatis</p>
                        <p class="mt-0.5 text-sm leading-6 text-primary-foreground/75">
                            Pastikan makanan masih layak konsumsi. Postingan otomatis berhenti tampil di katalog setelah batas konsumsi terlewati.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 text-xs font-medium text-primary-foreground/85 sm:justify-end">
                    <span class="rounded-full bg-white/10 px-3 py-1.5">Foto jelas</span>
                    <span class="rounded-full bg-white/10 px-3 py-1.5">Batas aman</span>
                    <span class="rounded-full bg-white/10 px-3 py-1.5">Titik jemput</span>
                </div>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
        @include('donor.food-posts._form', [
            'action' => route('donor.food-posts.store'),
            'method' => 'POST',
            'submitLabel' => 'Terbitkan Postingan Sekarang',
            'foodPost' => null,
            'prefillRequest' => $prefillRequest ?? null,
        ])
    </div>
</x-donor-form-layout>
