@php
    $inputClasses = 'block w-full rounded-xl border border-input bg-white px-3.5 py-2 text-sm text-foreground shadow-xs transition placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20';
@endphp

<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Flash messages --}}
        @if (session('success') || session('status'))
            <div class="mb-6 flex items-start gap-3 rounded-md border border-[#86EFAC] bg-[#DCFCE7] px-4 py-3 text-sm font-medium text-[#166534]" role="status">
                <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0" />
                <p>{{ session('success') ?? session('status') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 flex items-start gap-3 rounded-md border border-[#FCA5A5] bg-[#FEE2E2] px-4 py-3 text-sm font-medium text-[#B91C1C]" role="alert">
                <x-icon name="triangle-alert" class="mt-0.5 h-4 w-4 shrink-0" />
                <p>{{ session('error') }}</p>
            </div>
        @endif

        {{-- Header halaman --}}
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary">Portal Donatur · Request Board</p>
                <h1 class="mt-1.5 text-2xl font-semibold tracking-tight text-foreground">
                    {{ __('Papan Kebutuhan Pangan Penerima') }}
                </h1>
                <p class="mt-1.5 max-w-2xl text-sm text-muted-foreground">
                    {{ __('Lihat permintaan makanan mendesak dari lembaga penerima manfaat terverifikasi dan salurkan bantuan Anda secara langsung.') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('donor.requests.index', ['view' => 'open']) }}"
                    @class([
                        'inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-semibold transition',
                        'bg-primary text-primary-foreground' => $viewMode === 'open',
                        'border border-border bg-card text-foreground hover:bg-accent' => $viewMode !== 'open',
                    ])
                >
                    <x-icon name="message-square" class="h-4 w-4" />
                    Permintaan Terbuka
                    <span class="rounded-full bg-white/20 px-2 py-0.5 text-[11px]">{{ $openBoardCount }}</span>
                </a>

                <a
                    href="{{ route('donor.requests.index', ['view' => 'mine']) }}"
                    @class([
                        'inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-semibold transition',
                        'bg-primary text-primary-foreground' => $viewMode === 'mine',
                        'border border-border bg-card text-foreground hover:bg-accent' => $viewMode !== 'mine',
                    ])
                >
                    <x-icon name="clipboard-check" class="h-4 w-4" />
                    Respons Saya
                    <span class="rounded-full bg-muted px-2 py-0.5 text-[11px] text-foreground">{{ $myCommitmentsCount }}</span>
                </a>
            </div>
        </div>

        {{-- Ringkasan singkat --}}
        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-border bg-card p-5">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Permintaan Aktif</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-accent text-primary">
                        <x-icon name="message-square" class="h-4 w-4" />
                    </span>
                </div>
                <p class="mt-3 text-3xl font-semibold text-foreground">{{ $openBoardCount }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Kebutuhan terbuka yang menunggu donatur</p>
            </div>

            <div class="rounded-2xl border border-border bg-card p-5">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Mendesak / Darurat</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-secondary/10 text-secondary">
                        <x-icon name="triangle-alert" class="h-4 w-4" />
                    </span>
                </div>
                <p class="mt-3 text-3xl font-semibold text-foreground">{{ $urgentOpenCount }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Prioritas tinggi untuk segera dipenuhi</p>
            </div>

            <div class="rounded-2xl border border-border bg-card p-5">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Komitmen Anda</p>
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-muted text-foreground">
                        <x-icon name="check" class="h-4 w-4" />
                    </span>
                </div>
                <p class="mt-3 text-3xl font-semibold text-foreground">{{ $myCommitmentsCount }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Permintaan yang sedang/telah Anda penuhi</p>
            </div>
        </div>

        {{-- Filter pencarian --}}
        <form method="GET" action="{{ route('donor.requests.index') }}" class="mb-6 rounded-2xl border border-border bg-card p-4 sm:p-5">
            <input type="hidden" name="view" value="{{ $viewMode }}" />
            <div class="grid gap-3 sm:grid-cols-4">
                <div class="sm:col-span-2">
                    <label for="q" class="sr-only">Cari kebutuhan atau lembaga</label>
                    <input
                        id="q"
                        name="q"
                        type="search"
                        value="{{ $search }}"
                        placeholder="Cari makanan, deskripsi, atau nama lembaga penerima..."
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
                            <option value="{{ $level->value }}" @selected($selectedUrgency === $level->value)>
                                {{ $level->badgeLabel() }}
                            </option>
                        @endforeach
                    </select>

                    <button
                        type="submit"
                        class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-xl bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground transition hover:bg-primary/90"
                    >
                        <x-icon name="search" class="h-4 w-4" />
                        Filter
                    </button>
                </div>
            </div>
        </form>

        {{-- Daftar kartu kebutuhan --}}
        @if ($foodRequests->isEmpty())
            <div class="rounded-2xl border border-border bg-card p-8 sm:p-12">
                <div class="flex flex-col items-center justify-center text-center">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted text-muted-foreground">
                        <x-icon name="message-square" class="h-7 w-7" />
                    </span>
                    <h3 class="mt-4 text-base font-semibold text-foreground">
                        {{ $viewMode === 'mine' ? 'Anda belum merespons permintaan kebutuhan apa pun' : 'Tidak ada permintaan kebutuhan terbuka yang cocok' }}
                    </h3>
                    <p class="mt-1.5 max-w-md text-xs leading-5 text-muted-foreground">
                        @if ($viewMode === 'mine')
                            Pilih permintaan dari tab Permintaan Terbuka untuk mulai menyalurkan makanan ke lembaga yang membutuhkan.
                        @else
                            Saat ini belum ada permintaan aktif dari penerima atau coba sesuaikan filter pencarian Anda.
                        @endif
                    </p>
                </div>
            </div>
        @else
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($foodRequests as $req)
                    @php
                        $urgency = $req->urgency_level instanceof \App\Enums\UrgencyLevel
                            ? $req->urgency_level
                            : \App\Enums\UrgencyLevel::tryFrom((string) $req->urgency_level) ?? \App\Enums\UrgencyLevel::Mendesak;
                        $statusEnum = $req->status instanceof \App\Enums\FoodRequestStatus
                            ? $req->status
                            : \App\Enums\FoodRequestStatus::tryFrom((string) $req->status) ?? \App\Enums\FoodRequestStatus::Open;
                    @endphp

                    <article
                        x-data="{ fulfilOpen: false }"
                        class="flex flex-col justify-between rounded-2xl border border-border bg-card p-5 shadow-xs transition hover:border-primary/30"
                    >
                        <div>
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $urgency->badgeClass() }}">
                                    {{ $urgency->badgeLabel() }}
                                </span>
                                <x-status-badge :status="$statusEnum->value" />
                            </div>

                            <h2 class="mt-3 text-base font-semibold leading-snug text-foreground">
                                {{ $req->judul }}
                            </h2>

                            @if ($req->deskripsi)
                                <p class="mt-1.5 text-xs leading-5 text-muted-foreground">
                                    {{ $req->deskripsi }}
                                </p>
                            @endif

                            {{-- Profil penerima --}}
                            <div class="mt-4 rounded-xl border border-border bg-background p-3">
                                <div class="flex items-start gap-2.5">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-accent text-xs font-semibold text-primary">
                                        {{ str($req->recipient?->nama_lembaga ?? '?')->substr(0, 1)->upper() }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs font-semibold text-foreground">
                                            {{ $req->recipient?->nama_lembaga ?? 'Lembaga Penerima' }}
                                        </p>
                                        <p class="truncate text-[11px] text-muted-foreground">
                                            {{ $req->recipient?->jenis_lembaga }} · {{ $req->recipient?->alamat }}
                                        </p>
                                        @if ($req->distance_km !== null)
                                            <p class="mt-1 inline-flex items-center gap-1 text-[11px] font-medium text-primary">
                                                <x-icon name="map-pin" class="h-3 w-3" />
                                                {{ number_format($req->distance_km, 1, ',', '.') }} km dari lokasi Anda
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Detail kebutuhan --}}
                            <dl class="mt-4 space-y-2 text-xs text-muted-foreground">
                                <div class="flex items-center justify-between">
                                    <dt class="inline-flex items-center gap-1.5">
                                        <x-icon name="utensils-crossed" class="h-3.5 w-3.5 text-primary" />
                                        Kebutuhan Porsi
                                    </dt>
                                    <dd class="font-semibold text-foreground">{{ $req->jumlah_porsi }} porsi</dd>
                                </div>

                                @if ($req->category)
                                    <div class="flex items-center justify-between">
                                        <dt class="inline-flex items-center gap-1.5">
                                            <x-icon name="package" class="h-3.5 w-3.5" />
                                            Kategori
                                        </dt>
                                        <dd class="font-medium text-foreground">{{ $req->category->nama_kategori }}</dd>
                                    </div>
                                @endif

                                <div class="flex items-center justify-between">
                                    <dt class="inline-flex items-center gap-1.5">
                                        <x-icon name="navigation" class="h-3.5 w-3.5" />
                                        Metode
                                    </dt>
                                    <dd class="font-medium text-foreground">{{ $req->metodePenerimaanLabel() }}</dd>
                                </div>

                                <div class="flex items-center justify-between">
                                    <dt class="inline-flex items-center gap-1.5">
                                        <x-icon name="clock" class="h-3.5 w-3.5 text-secondary" />
                                        Batas Waktu
                                    </dt>
                                    <dd class="font-medium text-foreground">{{ $req->batas_waktu->format('d/m/Y H:i') }}</dd>
                                </div>
                            </dl>

                            @if ($req->claim)
                                <div class="mt-3 rounded-xl border border-primary/20 bg-accent/50 p-3 text-xs">
                                    <p class="font-semibold text-primary">Postingan Terhubung:</p>
                                    <p class="mt-0.5 text-foreground">{{ $req->claim->post?->judul ?? '-' }} ({{ $req->claim->jumlah_porsi }} porsi)</p>
                                    @if ($req->claim->kode_verifikasi)
                                        <p class="mt-1 font-mono text-[11px] font-semibold text-foreground">
                                            Kode Verifikasi Klaim: {{ $req->claim->kode_verifikasi }}
                                        </p>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Aksi Donatur --}}
                        <div class="mt-5 border-t border-border pt-4">
                            @if ($statusEnum === \App\Enums\FoodRequestStatus::Open)
                                <div class="flex flex-col gap-2">
                                    <button
                                        type="button"
                                        @click="fulfilOpen = ! fulfilOpen"
                                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-xs font-semibold text-primary-foreground transition hover:bg-primary/90"
                                    >
                                        <x-icon name="check" class="h-4 w-4" />
                                        <span x-text="fulfilOpen ? 'Tutup Opsi Pemenuhan' : 'Penuhi Kebutuhan Ini'">Penuhi Kebutuhan Ini</span>
                                    </button>

                                    <a
                                        href="{{ route('donor.food-posts.create', ['request_id' => $req->id]) }}"
                                        class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl border border-border bg-background px-3.5 py-2 text-xs font-medium text-foreground transition hover:bg-accent"
                                    >
                                        <x-icon name="plus" class="h-3.5 w-3.5" />
                                        Buat Postingan Baru untuk Ini
                                    </a>
                                </div>

                                {{-- Panel pemenuhan langsung --}}
                                <form
                                    x-show="fulfilOpen"
                                    x-cloak
                                    method="POST"
                                    action="{{ route('donor.requests.fulfil', $req) }}"
                                    class="mt-3 space-y-3 rounded-xl border border-border bg-muted/40 p-3.5"
                                >
                                    @csrf
                                    <div>
                                        <label for="post_id_{{ $req->id }}" class="block text-xs font-medium text-foreground">
                                            Alokasikan dari Postingan Tersedia (Opsional)
                                        </label>
                                        <select
                                            id="post_id_{{ $req->id }}"
                                            name="post_id"
                                            class="mt-1 block w-full rounded-lg border border-input bg-white px-2.5 py-2 text-xs text-foreground"
                                        >
                                            <option value="">Komitmen langsung tanpa pilih postingan</option>
                                            @foreach ($availablePosts as $post)
                                                <option value="{{ $post->id }}">
                                                    {{ $post->judul }} (Sisa {{ $post->sisa_porsi }} porsi)
                                                </option>
                                            @endforeach
                                        </select>
                                        <p class="mt-1 text-[11px] text-muted-foreground">
                                            Jika memilih postingan, klaim otomatis dibuat dan disetujui untuk penerima ini.
                                        </p>
                                    </div>

                                    <button
                                        type="submit"
                                        class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-primary-foreground transition hover:bg-primary/90"
                                    >
                                        Konfirmasi Pemenuhan
                                    </button>
                                </form>
                            @elseif ($statusEnum === \App\Enums\FoodRequestStatus::InProgress && $req->donor_id === $donor->id)
                                <form method="POST" action="{{ route('donor.requests.complete', $req) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button
                                        type="submit"
                                        class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground transition hover:bg-primary/90"
                                    >
                                        <x-icon name="check" class="h-4 w-4" />
                                        Tandai Selesai / Terpenuhi
                                    </button>
                                </form>
                            @else
                                <p class="text-center text-xs text-muted-foreground">
                                    Status: {{ $statusEnum->label() }}
                                </p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($foodRequests->hasPages())
                <div class="mt-6">
                    {{ $foodRequests->onEachSide(1)->links() }}
                </div>
            @endif
        @endif
    </div>
</x-app-layout>
