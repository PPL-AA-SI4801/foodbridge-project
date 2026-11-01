@php
    $post = $foodPost ?? null;
    $isCreate = $post === null;
    $currentJudul = old('judul', $post?->judul);
    $currentDeskripsi = old('deskripsi', $post?->deskripsi ?? '');
    $currentJumlahPorsi = old('jumlah_porsi', $post?->jumlah_porsi ?? 1);
    $currentAlamat = old('alamat', $post?->alamat ?? $donor->alamat);
    $currentLat = old('lat', $post?->lat ?? $donor->lat);
    $currentLng = old('lng', $post?->lng ?? $donor->lng);
    $currentBatasKonsumsi = old('batas_konsumsi', $post?->batas_konsumsi?->format('Y-m-d\TH:i'));
    $currentCategoryId = old('category_id', $post?->category_id);
    $currentCatatanKurir = (string) old('catatan_kurir', $post?->catatan_kurir ?? '');

    $selectedCategory = $categories->first(
        fn ($category) => (string) $category->id === (string) $currentCategoryId
    );

    $categoryIcons = [
        'karbohidrat' => 'package',
        'protein' => 'utensils-crossed',
        'sayuran' => 'leaf',
        'buah-buahan' => 'leaf',
        'lemak sehat' => 'droplets',
        'minuman' => 'cup-soda',
    ];

    $deadlinePreview = 'Belum ditentukan';

    if ($currentBatasKonsumsi) {
        try {
            $deadlinePreview = \Illuminate\Support\Carbon::parse($currentBatasKonsumsi)->format('d M Y, H:i');
        } catch (\Throwable) {
            $deadlinePreview = 'Format tanggal belum valid';
        }
    }

    $inputClasses = 'mt-2 block w-full rounded-xl border border-input bg-white px-3.5 py-2.5 text-sm text-foreground shadow-xs transition placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20';
    $inlineInputClasses = str_replace('mt-2 ', '', $inputClasses);
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    @if ($categories->isEmpty())
        <div class="flex items-start gap-3 rounded-xl border border-yellow-300 bg-yellow-50 px-4 py-3 text-sm text-yellow-800" role="alert">
            <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0" />
            <p>{{ __('Kategori makanan belum tersedia. Hubungi administrator sebelum membuat postingan.') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-destructive/20 bg-destructive/5 px-4 py-3 text-sm text-destructive" role="alert">
            <div class="flex items-start gap-3">
                <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0" />
                <div>
                    <p class="font-medium">Periksa kembali data postingan Anda.</p>
                    <ul class="mt-1 list-disc space-y-0.5 pl-4 text-destructive/90">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_25rem] xl:grid-cols-[minmax(0,1fr)_27rem]">
        <div class="space-y-6">
            <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-accent text-primary">
                            <x-icon name="utensils-crossed" class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="text-base font-semibold text-foreground">Detail Makanan Berlebih</h2>
                            <p class="mt-1 text-sm leading-6 text-muted-foreground">Jelaskan makanan yang masih layak disalurkan.</p>
                        </div>
                    </div>
                    <span class="rounded-full bg-muted px-2.5 py-1 text-[11px] font-medium text-muted-foreground">Wajib</span>
                </div>

                <div class="mt-5 space-y-5">
                    <div>
                        <label for="judul" class="text-sm font-medium text-foreground">Nama Makanan</label>
                        <input
                            id="judul"
                            name="judul"
                            type="text"
                            value="{{ $currentJudul }}"
                            class="{{ $inputClasses }}"
                            placeholder="Contoh: Nasi kuning ayam bakar"
                            required
                        />
                        <x-input-error :messages="$errors->get('judul')" class="mt-2" />
                    </div>

                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <label for="deskripsi" class="text-sm font-medium text-foreground">Deskripsi Singkat</label>
                            <span class="text-xs text-muted-foreground"><span id="deskripsi-count">{{ mb_strlen((string) $currentDeskripsi) }}</span>/255</span>
                        </div>
                        <textarea
                            id="deskripsi"
                            name="deskripsi"
                            rows="4"
                            maxlength="255"
                            class="{{ $inputClasses }} resize-none"
                            placeholder="Contoh: Nasi kuning, ayam bakar, telur, dan sayur. Dikemas rapi sejak pukul 10.00."
                            required
                        >{{ $currentDeskripsi }}</textarea>
                        <x-input-error :messages="$errors->get('deskripsi')" class="mt-2" />
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-accent text-primary">
                        <x-icon name="camera" class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-foreground">Foto Makanan</h2>
                        <p class="mt-1 text-sm leading-6 text-muted-foreground">Gunakan foto terang yang memperlihatkan kondisi makanan.</p>
                    </div>
                </div>

                <input
                    id="foto"
                    name="foto"
                    type="file"
                    accept=".jpg,.jpeg,.png"
                    class="sr-only"
                    @if (! $post) required @endif
                />

                <label
                    for="foto"
                    class="mt-5 flex cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-input bg-background px-5 py-6 text-center transition hover:border-primary/50 hover:bg-accent/50"
                >
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-accent text-primary">
                        <x-icon name="upload" class="h-5 w-5" />
                    </span>

                    <span
                        id="foto-preview-wrapper"
                        @class([
                            'mt-4 w-full overflow-hidden rounded-xl border border-border',
                            'hidden' => ! $post?->foto,
                        ])
                    >
                        <img
                            id="foto-preview"
                            src="{{ $post?->foto ? asset('storage/'.$post->foto) : '' }}"
                            alt="{{ $post?->judul ?? 'Pratinjau foto makanan' }}"
                            class="h-40 w-full object-cover"
                        />
                    </span>

                    <span id="foto-file-name" class="mt-4 max-w-full truncate text-sm font-medium text-foreground">
                        {{ $post?->foto ? 'Foto saat ini tersimpan' : 'Belum ada file dipilih' }}
                    </span>
                    <span class="mt-1 text-sm text-muted-foreground">
                        {{ $post ? 'Klik untuk mengganti foto. Kosongkan jika tidak ingin mengubah.' : 'Klik untuk memilih foto JPG, JPEG, atau PNG.' }}
                    </span>
                    <span class="mt-2 rounded-full bg-muted px-2.5 py-1 text-[11px] font-medium text-muted-foreground">Maksimal 2 MB</span>
                </label>

                <x-input-error :messages="$errors->get('foto')" class="mt-2" />
            </section>

            <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-accent text-primary">
                            <x-icon name="package" class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="text-base font-semibold text-foreground">Kategori Makanan</h2>
                            <p class="mt-1 text-sm leading-6 text-muted-foreground">Pilih satu kategori utama agar mudah ditemukan penerima.</p>
                        </div>
                    </div>
                    @if ($selectedCategory)
                        <span class="hidden rounded-full bg-accent px-2.5 py-1 text-[11px] font-medium text-primary sm:inline-flex">
                            {{ $selectedCategory->nama_kategori }}
                        </span>
                    @endif
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @forelse ($categories as $category)
                        @php
                            $categoryIcon = $categoryIcons[strtolower($category->nama_kategori)] ?? 'utensils-crossed';
                        @endphp

                        <label class="relative block cursor-pointer">
                            <input
                                type="radio"
                                name="category_id"
                                value="{{ $category->id }}"
                                class="peer sr-only"
                                @checked((string) $currentCategoryId === (string) $category->id)
                                required
                            />
                            <span class="flex h-full items-center gap-3 rounded-xl border border-border bg-background p-3.5 pr-10 transition peer-checked:border-primary peer-checked:bg-accent peer-focus-visible:ring-2 peer-focus-visible:ring-primary/30 peer-focus-visible:ring-offset-2">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-card text-primary">
                                    <x-icon :name="$categoryIcon" class="h-4 w-4" />
                                </span>
                                <span class="text-sm font-medium text-foreground">{{ $category->nama_kategori }}</span>
                            </span>
                            <span class="pointer-events-none absolute right-3 top-1/2 flex h-5 w-5 -translate-y-1/2 items-center justify-center rounded-full bg-primary text-primary-foreground opacity-0 transition peer-checked:opacity-100">
                                <x-icon name="check" class="h-3 w-3" />
                            </span>
                        </label>
                    @empty
                        <p class="rounded-xl border border-dashed border-border px-4 py-5 text-sm text-muted-foreground sm:col-span-2">
                            Belum ada kategori yang dapat dipilih.
                        </p>
                    @endforelse
                </div>

                <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
            </section>

            <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-accent text-primary">
                        <x-icon name="package" class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-foreground">Jumlah Porsi yang Disalurkan</h2>
                        <p class="mt-1 text-sm leading-6 text-muted-foreground">Perkirakan jumlah porsi siap santap yang tersedia saat ini.</p>
                    </div>
                </div>

                <div class="mt-5 flex flex-col gap-4 rounded-xl border border-border bg-background p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-foreground">Total porsi</p>
                        <p class="mt-1 text-xs text-muted-foreground">Minimal 1 porsi.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            data-portion-step="-1"
                            aria-label="Kurangi jumlah porsi"
                            class="flex h-10 w-10 items-center justify-center rounded-lg border border-border bg-card text-foreground transition hover:bg-accent focus:outline-none focus:ring-2 focus:ring-primary/20"
                        >
                            <x-icon name="minus" class="h-4 w-4" />
                        </button>
                        <input
                            id="jumlah_porsi"
                            name="jumlah_porsi"
                            type="number"
                            min="1"
                            step="1"
                            value="{{ $currentJumlahPorsi }}"
                            class="h-10 w-24 rounded-lg border border-input bg-white text-center text-lg font-semibold text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                            required
                        />
                        <button
                            type="button"
                            data-portion-step="1"
                            aria-label="Tambah jumlah porsi"
                            class="flex h-10 w-10 items-center justify-center rounded-lg border border-border bg-card text-foreground transition hover:bg-accent focus:outline-none focus:ring-2 focus:ring-primary/20"
                        >
                            <x-icon name="plus" class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <x-input-error :messages="$errors->get('jumlah_porsi')" class="mt-2" />
            </section>

            <section class="rounded-2xl border border-primary/15 bg-accent/70 p-5 sm:p-6">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary text-primary-foreground">
                        <x-icon name="shield-check" class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-foreground">Pernyataan Standar Higienitas</h2>
                        <p class="mt-2 text-sm leading-6 text-muted-foreground">
                            Dengan menerbitkan postingan, Anda menyatakan bahwa makanan yang dibagikan memenuhi standar keamanan pangan berikut.
                        </p>

                        <ul class="mt-4 space-y-2 text-sm text-foreground">
                            <li class="flex items-start gap-2">
                                <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                                <span>Makanan tidak basi, tidak berjamur, dan masih layak konsumsi.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                                <span>Kemasan bersih dan tidak terkontaminasi.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                                <span>Makanan disiapkan dengan peralatan dan tangan yang bersih.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-secondary/10 text-secondary">
                        <x-icon name="clock" class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-foreground">Batas Aman Konsumsi</h2>
                        <p class="mt-1 text-sm leading-6 text-muted-foreground">Tentukan waktu terakhir makanan masih aman dikonsumsi.</p>
                    </div>
                </div>

                <div class="mt-5">
                    <label for="batas_konsumsi" class="text-sm font-medium text-foreground">Batas Konsumsi</label>
                    <input
                        id="batas_konsumsi"
                        name="batas_konsumsi"
                        type="datetime-local"
                        value="{{ $currentBatasKonsumsi }}"
                        class="{{ $inputClasses }}"
                        required
                    />
                    <x-input-error :messages="$errors->get('batas_konsumsi')" class="mt-2" />
                </div>

                <div class="mt-3">
                    <p class="text-xs font-medium text-muted-foreground">Pilihan cepat</p>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        @foreach ([2 => '2 jam', 4 => '4 jam', 8 => '8 jam', 12 => '12 jam'] as $hours => $label)
                            <button
                                type="button"
                                data-deadline-preset="{{ $hours }}"
                                aria-pressed="false"
                                class="rounded-lg border border-border bg-background px-3 py-2 text-xs font-medium text-foreground transition hover:bg-accent aria-pressed:border-primary aria-pressed:bg-primary aria-pressed:text-primary-foreground"
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="mt-4 flex items-start gap-3 rounded-xl border border-border bg-background p-3.5">
                    <x-icon name="clock" class="mt-0.5 h-4 w-4 shrink-0 text-secondary" />
                    <div>
                        <p class="text-xs font-medium text-muted-foreground">Batas konsumsi</p>
                        <p id="deadline-preview" class="mt-0.5 text-sm font-semibold text-foreground">{{ $deadlinePreview }}</p>
                    </div>
                </div>

                <p class="mt-3 flex items-start gap-2 text-xs leading-5 text-muted-foreground">
                    <x-icon name="info" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                    <span>Setelah batas ini terlewati, postingan otomatis berhenti tampil di katalog penerima.</span>
                </p>
            </section>

            <section class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-accent text-primary">
                        <x-icon name="map-pin" class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-foreground">Titik & Instruksi Jemput</h2>
                        <p class="mt-1 text-sm leading-6 text-muted-foreground">Pastikan titik penjemputan akurat dan mudah ditemukan kurir.</p>
                    </div>
                </div>

                <div class="mt-5">
                    <label for="alamat" class="text-sm font-medium text-foreground">Lokasi Donatur Terdaftar</label>
                    <input
                        id="alamat"
                        name="alamat"
                        type="text"
                        value="{{ $currentAlamat }}"
                        class="{{ $inputClasses }}"
                        placeholder="Alamat lengkap titik jemput"
                        required
                    />
                    <x-input-error :messages="$errors->get('alamat')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <label for="food-post-address-search" class="text-sm font-medium text-foreground">Cari Alamat</label>
                    <div class="mt-2 flex gap-2">
                        <input
                            type="text"
                            id="food-post-address-search"
                            class="{{ $inlineInputClasses }} min-w-0 flex-1"
                            placeholder="Cari jalan, gedung, atau kecamatan"
                        />
                        <button
                            type="button"
                            id="food-post-search-address"
                            class="inline-flex h-[42px] shrink-0 items-center justify-center gap-2 rounded-xl bg-primary px-3.5 text-sm font-medium text-primary-foreground transition hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/20"
                        >
                            <x-icon name="search" class="h-4 w-4" />
                            <span class="sr-only sm:not-sr-only">Cari</span>
                        </button>
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-2 gap-2">
                    <button
                        type="button"
                        id="food-post-use-donor-location"
                        data-donor-lat="{{ $donor->lat }}"
                        data-donor-lng="{{ $donor->lng }}"
                        data-donor-address="{{ $donor->alamat }}"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-border bg-background px-3 py-2 text-xs font-medium text-foreground transition hover:bg-accent focus:outline-none focus:ring-2 focus:ring-primary/20 disabled:cursor-not-allowed disabled:opacity-50"
                        @disabled(! $donor->lat || ! $donor->lng)
                    >
                        <x-icon name="map-pin" class="h-3.5 w-3.5" />
                        Lokasi terdaftar
                    </button>
                    <button
                        type="button"
                        id="food-post-detect-location"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-border bg-background px-3 py-2 text-xs font-medium text-foreground transition hover:bg-accent focus:outline-none focus:ring-2 focus:ring-primary/20"
                    >
                        <x-icon name="navigation" class="h-3.5 w-3.5" />
                        Lokasi saat ini
                    </button>
                </div>

                <div id="food-post-map" class="mt-4 h-64 w-full overflow-hidden rounded-xl border border-border bg-muted"></div>

                <input type="hidden" id="lat" name="lat" value="{{ $currentLat }}" />
                <input type="hidden" id="lng" name="lng" value="{{ $currentLng }}" />

                <p id="food-post-location-status" class="mt-3 flex items-start gap-2 text-xs leading-5 text-muted-foreground">
                    <x-icon name="info" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                    <span>
                        {{ $currentLat && $currentLng ? 'Titik penjemputan sudah diisi. Geser pin jika perlu disesuaikan.' : 'Tentukan titik jemput lewat pencarian, lokasi terdaftar, atau klik peta.' }}
                    </span>
                </p>

                <div class="mt-4 grid grid-cols-2 gap-2">
                    <div>
                        <label for="lat-display" class="text-[11px] font-medium text-muted-foreground">Latitude</label>
                        <input id="lat-display" type="text" readonly value="{{ $currentLat }}" class="mt-1 w-full rounded-lg border border-border bg-muted/60 px-2.5 py-2 text-xs text-muted-foreground" />
                    </div>
                    <div>
                        <label for="lng-display" class="text-[11px] font-medium text-muted-foreground">Longitude</label>
                        <input id="lng-display" type="text" readonly value="{{ $currentLng }}" class="mt-1 w-full rounded-lg border border-border bg-muted/60 px-2.5 py-2 text-xs text-muted-foreground" />
                    </div>
                </div>

                <x-input-error :messages="$errors->get('lat')" class="mt-2" />
                <x-input-error :messages="$errors->get('lng')" class="mt-2" />

                <div class="mt-5 border-t border-border pt-5" x-data="{ count: {{ mb_strlen($currentCatatanKurir) }} }">
                    <label for="catatan-kurir" class="flex items-center justify-between gap-3 text-sm font-medium text-foreground">
                        <span>Catatan Khusus untuk Kurir / Resepsionis</span>
                        <span class="rounded-full bg-muted px-2 py-0.5 text-[10px] font-medium text-muted-foreground">Opsional</span>
                    </label>
                    <textarea
                        id="catatan-kurir"
                        name="catatan_kurir"
                        rows="3"
                        maxlength="500"
                        x-on:input="count = $event.target.value.length"
                        placeholder="Contoh: Gunakan pintu samping, hubungi resepsionis sebelum tiba, atau simpan makanan di meja penerimaan."
                        class="mt-2 block w-full rounded-xl border border-border bg-background px-3.5 py-3 text-sm text-foreground shadow-xs transition placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 @error('catatan_kurir') border-destructive focus:border-destructive focus:ring-destructive/20 @enderror"
                    >{{ $currentCatatanKurir }}</textarea>
                    <div class="mt-2 flex items-start justify-between gap-4 text-xs leading-5 text-muted-foreground">
                        <p class="flex items-start gap-2">
                            <x-icon name="info" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                            <span>Tambahkan petunjuk pengambilan agar proses serah terima lebih mudah.</span>
                        </p>
                        <span class="shrink-0 tabular-nums"><span x-text="count">{{ mb_strlen($currentCatatanKurir) }}</span>/500</span>
                    </div>
                    <x-input-error :messages="$errors->get('catatan_kurir')" class="mt-2" />
                </div>
            </section>
        </aside>
    </div>

    <div class="rounded-2xl border border-border bg-card p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <a href="{{ route('donor.food-posts.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-muted-foreground transition hover:text-foreground">
                    <x-icon name="arrow-left" class="h-4 w-4" />
                    Kembali ke daftar postingan
                </a>
                <p class="mt-2 text-sm leading-6 text-muted-foreground">
                    Periksa kembali foto, batas konsumsi, dan titik jemput sebelum menerbitkan.
                </p>
                @if ($isCreate)
                    <p class="mt-1 text-xs text-muted-foreground">
                        Draf belum tersedia pada versi ini.
                    </p>
                @endif
            </div>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center">
                @if ($isCreate)
                    <button
                        type="button"
                        disabled
                        title="Draf belum tersedia pada versi ini."
                        class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-xl border border-border bg-muted px-4 py-2.5 text-sm font-medium text-muted-foreground"
                    >
                        <x-icon name="file-text" class="h-4 w-4" />
                        Simpan sebagai Draf
                    </button>
                @endif

                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/30 disabled:cursor-not-allowed disabled:opacity-50"
                    @disabled($categories->isEmpty())
                >
                    {{ $submitLabel }}
                    <x-icon name="chevron-right" class="h-4 w-4" />
                </button>
            </div>
        </div>
    </div>
</form>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const description = document.getElementById('deskripsi');
        const descriptionCount = document.getElementById('deskripsi-count');

        if (description && descriptionCount) {
            const updateDescriptionCount = function () {
                descriptionCount.textContent = description.value.length;
            };

            description.addEventListener('input', updateDescriptionCount);
            updateDescriptionCount();
        }

        const photoInput = document.getElementById('foto');
        const photoFileName = document.getElementById('foto-file-name');
        const photoPreview = document.getElementById('foto-preview');
        const photoPreviewWrapper = document.getElementById('foto-preview-wrapper');

        if (photoInput && photoFileName) {
            photoInput.addEventListener('change', function () {
                const file = photoInput.files && photoInput.files[0];

                if (!file) {
                    return;
                }

                photoFileName.textContent = file.name + ' · ' + formatFileSize(file.size);

                if (photoPreview && photoPreviewWrapper) {
                    photoPreview.src = URL.createObjectURL(file);
                    photoPreviewWrapper.classList.remove('hidden');
                }
            });
        }

        function formatFileSize(bytes) {
            if (bytes < 1024) {
                return bytes + ' B';
            }

            if (bytes < 1024 * 1024) {
                return (bytes / 1024).toFixed(1) + ' KB';
            }

            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }

        const portionInput = document.getElementById('jumlah_porsi');

        if (portionInput) {
            document.querySelectorAll('[data-portion-step]').forEach(function (button) {
                button.addEventListener('click', function () {
                    const step = parseInt(button.dataset.portionStep, 10);
                    const min = parseInt(portionInput.min, 10) || 1;
                    const current = parseInt(portionInput.value, 10) || min;
                    portionInput.value = Math.max(min, current + step);
                    portionInput.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
        }

        const deadlineInput = document.getElementById('batas_konsumsi');
        const deadlinePreview = document.getElementById('deadline-preview');
        const deadlinePresets = document.querySelectorAll('[data-deadline-preset]');

        function updateDeadlinePreview() {
            if (!deadlineInput || !deadlinePreview) {
                return;
            }

            if (!deadlineInput.value) {
                deadlinePreview.textContent = 'Belum ditentukan';
                return;
            }

            const deadline = new Date(deadlineInput.value);

            if (Number.isNaN(deadline.getTime())) {
                deadlinePreview.textContent = 'Format tanggal belum valid';
                return;
            }

            deadlinePreview.textContent = new Intl.DateTimeFormat('id-ID', {
                dateStyle: 'medium',
                timeStyle: 'short'
            }).format(deadline);
        }

        if (deadlineInput) {
            deadlineInput.addEventListener('input', function () {
                deadlinePresets.forEach(function (button) {
                    button.setAttribute('aria-pressed', 'false');
                });
                updateDeadlinePreview();
            });

            deadlineInput.addEventListener('change', updateDeadlinePreview);
            updateDeadlinePreview();
        }

        deadlinePresets.forEach(function (button) {
            button.addEventListener('click', function () {
                if (!deadlineInput) {
                    return;
                }

                const hours = parseInt(button.dataset.deadlinePreset, 10);
                const deadline = new Date(Date.now() + hours * 60 * 60 * 1000);
                deadline.setSeconds(0, 0);

                const pad = function (value) {
                    return String(value).padStart(2, '0');
                };

                deadlineInput.value = deadline.getFullYear()
                    + '-' + pad(deadline.getMonth() + 1)
                    + '-' + pad(deadline.getDate())
                    + 'T' + pad(deadline.getHours())
                    + ':' + pad(deadline.getMinutes());

                deadlinePresets.forEach(function (preset) {
                    preset.setAttribute('aria-pressed', preset === button ? 'true' : 'false');
                });

                updateDeadlinePreview();
            });
        });

        initFoodPostMap();
    });

    function initFoodPostMap() {
        if (typeof L === 'undefined') {
            return;
        }

        const latInput = document.getElementById('lat');
        const lngInput = document.getElementById('lng');
        const latDisplay = document.getElementById('lat-display');
        const lngDisplay = document.getElementById('lng-display');
        const locationStatus = document.getElementById('food-post-location-status');
        const addressInput = document.getElementById('alamat');
        const searchInput = document.getElementById('food-post-address-search');
        const searchButton = document.getElementById('food-post-search-address');
        const detectButton = document.getElementById('food-post-detect-location');
        const donorLocationButton = document.getElementById('food-post-use-donor-location');
        const mapElement = document.getElementById('food-post-map');

        if (!latInput || !lngInput || !mapElement) {
            return;
        }

        const initialLat = latInput.value ? parseFloat(latInput.value) : null;
        const initialLng = lngInput.value ? parseFloat(lngInput.value) : null;
        const hasInitialCoordinates = Number.isFinite(initialLat) && Number.isFinite(initialLng);
        const defaultLat = -2.5489;
        const defaultLng = 118.0149;

        const map = L.map('food-post-map').setView(
            [hasInitialCoordinates ? initialLat : defaultLat, hasInitialCoordinates ? initialLng : defaultLng],
            hasInitialCoordinates ? 15 : 5
        );

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        let marker = null;

        function updateLocationStatus(message) {
            if (locationStatus) {
                const text = locationStatus.querySelector('span');

                if (text) {
                    text.textContent = message;
                }
            }
        }

        function setCoords(lat, lng) {
            const numericLat = parseFloat(lat);
            const numericLng = parseFloat(lng);

            if (!Number.isFinite(numericLat) || !Number.isFinite(numericLng)) {
                return;
            }

            latInput.value = numericLat;
            lngInput.value = numericLng;

            if (latDisplay) {
                latDisplay.value = numericLat.toFixed(6);
            }

            if (lngDisplay) {
                lngDisplay.value = numericLng.toFixed(6);
            }

            if (marker) {
                marker.setLatLng([numericLat, numericLng]);
            } else {
                marker = L.marker([numericLat, numericLng], { draggable: true }).addTo(map);
                marker.on('dragend', function (event) {
                    const position = event.target.getLatLng();
                    setCoords(position.lat, position.lng);
                    updateLocationStatus('Titik penjemputan diperbarui. Pastikan alamat sesuai dengan pin.');
                });
            }

            updateLocationStatus('Titik penjemputan siap. Geser pin jika perlu disesuaikan.');
        }

        if (hasInitialCoordinates) {
            setCoords(initialLat, initialLng);
        }

        map.on('click', function (event) {
            setCoords(event.latlng.lat, event.latlng.lng);
            updateLocationStatus('Titik penjemputan dipilih dari peta.');
        });

        if (searchButton && searchInput) {
            const performSearch = async function () {
                const query = searchInput.value.trim();

                if (!query) {
                    updateLocationStatus('Masukkan alamat yang ingin dicari.');
                    return;
                }

                const originalLabel = searchButton.innerHTML;
                searchButton.disabled = true;
                searchButton.classList.add('opacity-70');
                updateLocationStatus('Mencari alamat...');

                try {
                    const response = await fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&accept-language=id&q=' + encodeURIComponent(query));
                    const data = await response.json();

                    if (data && data.length) {
                        const result = data[0];
                        const lat = parseFloat(result.lat);
                        const lng = parseFloat(result.lon);

                        setCoords(lat, lng);
                        map.setView([lat, lng], 16);

                        if (addressInput && result.display_name) {
                            addressInput.value = result.display_name.slice(0, 255);
                        }

                        updateLocationStatus('Alamat ditemukan. Sesuaikan pin bila perlu.');
                    } else {
                        updateLocationStatus('Alamat tidak ditemukan. Coba kata kunci lain.');
                    }
                } catch (error) {
                    updateLocationStatus('Pencarian alamat gagal. Periksa koneksi lalu coba lagi.');
                } finally {
                    searchButton.disabled = false;
                    searchButton.classList.remove('opacity-70');
                    searchButton.innerHTML = originalLabel;
                }
            };

            searchButton.addEventListener('click', performSearch);
            searchInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    performSearch();
                }
            });
        }

        if (detectButton) {
            detectButton.addEventListener('click', function () {
                if (!navigator.geolocation) {
                    updateLocationStatus('Browser tidak mendukung deteksi lokasi.');
                    return;
                }

                updateLocationStatus('Mengambil lokasi saat ini...');

                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        setCoords(lat, lng);
                        map.setView([lat, lng], 16);
                    },
                    function () {
                        updateLocationStatus('Lokasi saat ini tidak dapat diambil. Gunakan lokasi terdaftar atau cari alamat.');
                    },
                    { enableHighAccuracy: true, timeout: 10000 }
                );
            });
        }

        if (donorLocationButton && !donorLocationButton.disabled) {
            donorLocationButton.addEventListener('click', function () {
                const lat = parseFloat(donorLocationButton.dataset.donorLat);
                const lng = parseFloat(donorLocationButton.dataset.donorLng);

                if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                    updateLocationStatus('Lokasi terdaftar belum memiliki koordinat.');
                    return;
                }

                setCoords(lat, lng);
                map.setView([lat, lng], 16);

                if (addressInput && donorLocationButton.dataset.donorAddress) {
                    addressInput.value = donorLocationButton.dataset.donorAddress;
                }

                updateLocationStatus('Lokasi terdaftar digunakan sebagai titik jemput.');
            });
        }

        setTimeout(function () {
            map.invalidateSize();
        }, 150);
    }
</script>
