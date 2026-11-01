<x-donor-form-layout title="Ubah Postingan Makanan - FoodBridge">
    <section class="border-b border-border bg-[#FCFCFB]">
        <div class="mx-auto max-w-7xl px-4 py-9 sm:px-6 sm:py-10 lg:px-8">
            <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <div class="max-w-3xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary">Portal Donatur</p>
                    <h1 class="mt-3 text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
                        Ubah Postingan Makanan
                    </h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-muted-foreground sm:text-base">
                        Perbarui detail makanan, batas konsumsi, atau titik jemput agar informasi yang dilihat penerima tetap akurat.
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
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
        @include('donor.food-posts._form', [
            'action' => route('donor.food-posts.update', $foodPost),
            'method' => 'PUT',
            'submitLabel' => 'Simpan Perubahan',
            'foodPost' => $foodPost,
        ])
    </div>
</x-donor-form-layout>
