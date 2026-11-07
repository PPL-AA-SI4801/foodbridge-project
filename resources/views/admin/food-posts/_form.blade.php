@php
    $post = $foodPost ?? null;
    $isCreate = $post === null;
    $currentDonorId = old('donor_id', $post?->donor_id);
    $currentJudul = old('judul', $post?->judul);
    $currentDeskripsi = old('deskripsi', $post?->deskripsi ?? '');
    $currentJumlahPorsi = old('jumlah_porsi', $post?->jumlah_porsi ?? 10);
    $currentSisaPorsi = old('sisa_porsi', $post?->sisa_porsi ?? $currentJumlahPorsi);
    $currentAlamat = old('alamat', $post?->alamat ?? '');
    $currentCatatanKurir = old('catatan_kurir', $post?->catatan_kurir ?? '');
    $currentLat = old('lat', $post?->lat ?? -6.200000);
    $currentLng = old('lng', $post?->lng ?? 106.816666);
    $currentBatasKonsumsi = old('batas_konsumsi', $post?->batas_konsumsi?->format('Y-m-d\TH:i') ?? now()->addHours(6)->format('Y-m-d\TH:i'));
    $currentCategoryId = old('category_id', $post?->category_id);
    $currentStatus = old('status', $post?->status?->value ?? 'tersedia');

    $inputClasses = 'mt-1.5 block w-full rounded-lg border border-input bg-white px-3.5 py-2.5 text-sm text-foreground shadow-xs transition placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20';
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-[#FCA5A5] bg-[#FEE2E2] px-4 py-3 text-sm text-[#B91C1C]" role="alert">
            <p class="font-semibold">Periksa kembali data postingan makanan:</p>
            <ul class="mt-1 list-disc space-y-0.5 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Kolom Kiri: Informasi Makanan & Donatur --}}
        <div class="space-y-5 rounded-xl border border-border bg-card p-6">
            <h2 class="text-base font-semibold text-foreground">Informasi Utama Makanan</h2>

            <div>
                <label for="donor_id" class="block text-sm font-medium text-foreground">Donatur Pemilik Postingan</label>
                <select id="donor_id" name="donor_id" class="{{ $inputClasses }}" required>
                    <option value="">Pilih usaha donatur...</option>
                    @foreach ($donors as $donor)
                        <option
                            value="{{ $donor->id }}"
                            data-alamat="{{ $donor->alamat }}"
                            data-lat="{{ $donor->lat }}"
                            data-lng="{{ $donor->lng }}"
                            @selected((string) $currentDonorId === (string) $donor->id)
                        >
                            {{ $donor->nama_usaha }} ({{ $donor->jenis_usaha }})
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('donor_id')" class="mt-1.5" />
            </div>

            <div>
                <label for="judul" class="block text-sm font-medium text-foreground">Nama Makanan</label>
                <input
                    id="judul"
                    name="judul"
                    type="text"
                    value="{{ $currentJudul }}"
                    placeholder="Contoh: Nasi Kotak Ayam Bakar"
                    class="{{ $inputClasses }}"
                    required
                />
                <x-input-error :messages="$errors->get('judul')" class="mt-1.5" />
            </div>

            <div>
                <label for="category_id" class="block text-sm font-medium text-foreground">Kategori Gizi</label>
                <select id="category_id" name="category_id" class="{{ $inputClasses }}" required>
                    <option value="">Pilih kategori...</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) $currentCategoryId === (string) $category->id)>
                            {{ $category->nama_kategori }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('category_id')" class="mt-1.5" />
            </div>

            <div>
                <label for="deskripsi" class="block text-sm font-medium text-foreground">Deskripsi Singkat</label>
                <textarea
                    id="deskripsi"
                    name="deskripsi"
                    rows="3"
                    maxlength="255"
                    class="{{ $inputClasses }}"
                    placeholder="Deskripsi isi makanan dan kondisi pengemasan"
                    required
                >{{ $currentDeskripsi }}</textarea>
                <x-input-error :messages="$errors->get('deskripsi')" class="mt-1.5" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="jumlah_porsi" class="block text-sm font-medium text-foreground">Total Porsi</label>
                    <input
                        id="jumlah_porsi"
                        name="jumlah_porsi"
                        type="number"
                        min="1"
                        value="{{ $currentJumlahPorsi }}"
                        class="{{ $inputClasses }}"
                        required
                    />
                    <x-input-error :messages="$errors->get('jumlah_porsi')" class="mt-1.5" />
                </div>

                <div>
                    <label for="sisa_porsi" class="block text-sm font-medium text-foreground">Sisa Porsi Tersedia</label>
                    <input
                        id="sisa_porsi"
                        name="sisa_porsi"
                        type="number"
                        min="0"
                        value="{{ $currentSisaPorsi }}"
                        class="{{ $inputClasses }}"
                        @if (! $isCreate) required @endif
                    />
                    <x-input-error :messages="$errors->get('sisa_porsi')" class="mt-1.5" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="batas_konsumsi" class="block text-sm font-medium text-foreground">Batas Konsumsi</label>
                    <input
                        id="batas_konsumsi"
                        name="batas_konsumsi"
                        type="datetime-local"
                        value="{{ $currentBatasKonsumsi }}"
                        class="{{ $inputClasses }}"
                        required
                    />
                    <x-input-error :messages="$errors->get('batas_konsumsi')" class="mt-1.5" />
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-foreground">Status Postingan</label>
                    <select id="status" name="status" class="{{ $inputClasses }}">
                        @foreach (\App\Enums\FoodPostStatus::cases() as $statusCase)
                            <option value="{{ $statusCase->value }}" @selected($currentStatus === $statusCase->value)>
                                {{ ucfirst($statusCase->value) }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-1.5" />
                </div>
            </div>
        </div>

        {{-- Kolom Kanan: Foto & Lokasi Penjemputan --}}
        <div class="space-y-5 rounded-xl border border-border bg-card p-6">
            <h2 class="text-base font-semibold text-foreground">Foto &amp; Lokasi Penjemputan</h2>

            <div>
                <label for="foto" class="block text-sm font-medium text-foreground">
                    Foto Makanan {{ $isCreate ? '(Wajib)' : '(Kosongkan jika tidak diganti)' }}
                </label>
                @if ($post?->foto)
                    <div class="mt-2 mb-3 overflow-hidden rounded-lg border border-border">
                        <img src="{{ asset('storage/'.$post->foto) }}" alt="{{ $post->judul }}" class="h-36 w-full object-cover" />
                    </div>
                @endif
                <input
                    id="foto"
                    name="foto"
                    type="file"
                    accept=".jpg,.jpeg,.png"
                    class="{{ $inputClasses }}"
                    @if ($isCreate) required @endif
                />
                <p class="mt-1 text-xs text-muted-foreground">Format JPG, JPEG, atau PNG (maks. 2 MB).</p>
                <x-input-error :messages="$errors->get('foto')" class="mt-1.5" />
            </div>

            <div>
                <label for="alamat" class="block text-sm font-medium text-foreground">Alamat Titik Jemput</label>
                <input
                    id="alamat"
                    name="alamat"
                    type="text"
                    value="{{ $currentAlamat }}"
                    placeholder="Alamat lengkap penjemputan makanan"
                    class="{{ $inputClasses }}"
                    required
                />
                <x-input-error :messages="$errors->get('alamat')" class="mt-1.5" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="lat" class="block text-sm font-medium text-foreground">Latitude</label>
                    <input
                        id="lat"
                        name="lat"
                        type="number"
                        step="any"
                        value="{{ $currentLat }}"
                        class="{{ $inputClasses }}"
                        required
                    />
                    <x-input-error :messages="$errors->get('lat')" class="mt-1.5" />
                </div>

                <div>
                    <label for="lng" class="block text-sm font-medium text-foreground">Longitude</label>
                    <input
                        id="lng"
                        name="lng"
                        type="number"
                        step="any"
                        value="{{ $currentLng }}"
                        class="{{ $inputClasses }}"
                        required
                    />
                    <x-input-error :messages="$errors->get('lng')" class="mt-1.5" />
                </div>
            </div>

            <div>
                <label for="catatan_kurir" class="block text-sm font-medium text-foreground">Catatan Kurir / Pengambilan (Opsional)</label>
                <textarea
                    id="catatan_kurir"
                    name="catatan_kurir"
                    rows="3"
                    maxlength="500"
                    class="{{ $inputClasses }}"
                    placeholder="Petunjuk khusus bagi kurir atau penerima saat menjemput makanan"
                >{{ $currentCatatanKurir }}</textarea>
                <x-input-error :messages="$errors->get('catatan_kurir')" class="mt-1.5" />
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between gap-4 rounded-xl border border-border bg-card px-6 py-4">
        <a
            href="{{ route('admin.food-posts.index') }}"
            class="inline-flex items-center gap-2 text-sm font-medium text-muted-foreground transition hover:text-foreground"
        >
            <x-icon name="arrow-left" class="h-4 w-4" />
            Kembali ke daftar postingan
        </a>

        <button
            type="submit"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/30"
        >
            <x-icon name="check" class="h-4 w-4" />
            {{ $submitLabel }}
        </button>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const donorSelect = document.getElementById('donor_id');
        const addressInput = document.getElementById('alamat');
        const latInput = document.getElementById('lat');
        const lngInput = document.getElementById('lng');

        if (donorSelect) {
            donorSelect.addEventListener('change', function () {
                const option = donorSelect.options[donorSelect.selectedIndex];
                if (!option) return;

                if (option.dataset.alamat && (!addressInput.value || addressInput.value.trim() === '')) {
                    addressInput.value = option.dataset.alamat;
                }
                if (option.dataset.lat) {
                    latInput.value = option.dataset.lat;
                }
                if (option.dataset.lng) {
                    lngInput.value = option.dataset.lng;
                }
            });
        }
    });
</script>
