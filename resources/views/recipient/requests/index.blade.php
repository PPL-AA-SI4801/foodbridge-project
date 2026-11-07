@php
    $quotaExhausted = $remainingOpenQuota <= 0;

    $summary = [];
    foreach (collect($statusCounts ?? []) as $status => $total) {
        $summary[(string) $status] = (int) $total;
    }

    $stats = [
        'open' => ['label' => 'Terbuka', 'hint' => 'Menunggu respons donatur', 'icon' => 'message-square'],
        'in_progress' => ['label' => 'Sedang Diproses', 'hint' => 'Donatur berkomitmen', 'icon' => 'clock'],
        'fulfilled' => ['label' => 'Terpenuhi', 'hint' => 'Kebutuhan telah diterima', 'icon' => 'check'],
        'expired' => ['label' => 'Kedaluwarsa', 'hint' => 'Melewati batas waktu', 'icon' => 'info'],
    ];

    $inputClasses = 'mt-1.5 block w-full rounded-xl border border-input bg-white px-3.5 py-2.5 text-sm text-foreground shadow-xs transition placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20';
@endphp

<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8" x-data="{ showCreateForm: {{ $errors->any() ? 'true' : 'false' }} }">
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
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary">Penerima / Yayasan · Request Board</p>
                <h1 class="mt-1.5 text-2xl font-semibold tracking-tight text-foreground">
                    {{ __('Siarkan Kebutuhan Pangan') }}
                </h1>
                <p class="mt-1.5 max-w-2xl text-sm text-muted-foreground">
                    {{ __('Ajukan kebutuhan makanan mendesak lembaga Anda secara proaktif agar Donatur terverifikasi dapat membantu memenuhinya.') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    @click="showCreateForm = ! showCreateForm"
                    @disabled($quotaExhausted)
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/30 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <x-icon name="plus" class="h-4 w-4" />
                    <span x-text="showCreateForm ? 'Tutup Formulir' : 'Buat Permintaan Kebutuhan'">Buat Permintaan Kebutuhan</span>
                </button>
            </div>
        </div>

        {{-- Kuota permintaan terbuka + filter status --}}
        <div class="mb-6 grid gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1.8fr)]">
            <section class="rounded-2xl border border-border bg-card p-5 sm:p-6" aria-labelledby="open-quota-title">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-accent text-primary">
                            <x-icon name="message-square" class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 id="open-quota-title" class="text-sm font-semibold text-foreground">Batas Permintaan Terbuka</h2>
                            <p class="mt-0.5 text-xs text-muted-foreground">Maksimal {{ $maxOpen }} permintaan terbuka secara bersamaan.</p>
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
                        {{ $quotaExhausted ? 'Batas Tercapai' : 'Slot Tersedia' }}
                    </span>
                </div>

                <div class="mt-5 flex items-baseline gap-2">
                    <span class="text-3xl font-semibold tracking-tight text-foreground">{{ $openCount }}</span>
                    <span class="text-sm text-muted-foreground">dari {{ $maxOpen }} permintaan aktif terbuka</span>
                </div>

                <div class="mt-3 grid grid-cols-3 gap-2">
                    @for ($slot = 1; $slot <= $maxOpen; $slot++)
                        <span @class([
                            'h-2.5 rounded-full',
                            'bg-secondary' => $slot <= $openCount && $quotaExhausted,
                            'bg-primary' => $slot <= $openCount && ! $quotaExhausted,
                            'border border-border bg-muted' => $slot > $openCount,
                        ])></span>
                    @endfor
                </div>

                <p class="mt-4 text-xs leading-5 text-muted-foreground">
                    @if ($quotaExhausted)
                        Anda sudah memiliki {{ $maxOpen }} permintaan terbuka. Batalkan atau tunggu permintaan dipenuhi sebelum membuat yang baru.
                    @else
                        Anda masih dapat menyiarkan {{ $remainingOpenQuota }} permintaan baru ke Request Board.
                    @endif
                </p>
            </section>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                @foreach ($stats as $key => $stat)
                    @php
                        $isActive = $activeFilter === $key;
                    @endphp
                    <a
                        href="{{ route('recipient.requests.index', $isActive ? [] : ['status' => $key]) }}"
                        @class([
                            'group flex flex-col justify-between rounded-2xl border bg-card p-4 transition focus:outline-none focus:ring-2 focus:ring-primary/30 sm:p-5',
                            'border-primary ring-2 ring-primary/15' => $isActive,
                            'border-border hover:border-primary/30' => ! $isActive,
                        ])
                    >
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">{{ $stat['label'] }}</p>
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl border border-border bg-muted text-muted-foreground">
                                <x-icon :name="$stat['icon']" class="h-4 w-4" />
                            </span>
                        </div>
                        <div class="mt-3">
                            <p class="text-2xl font-semibold tracking-tight text-foreground">
                                {{ number_format($summary[$key] ?? 0, 0, ',', '.') }}
                            </p>
                            <p class="mt-1 text-[11px] text-muted-foreground">{{ $stat['hint'] }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Formulir Buat Permintaan Kebutuhan --}}
        <section
            x-show="showCreateForm"
            x-cloak
            class="mb-8 overflow-hidden rounded-2xl border border-border bg-card shadow-sm"
            aria-labelledby="create-request-title"
        >
            <div class="border-b border-border bg-muted/40 px-5 py-4 sm:px-6">
                <h2 id="create-request-title" class="text-base font-semibold text-foreground">Formulir Permintaan Kebutuhan Makanan</h2>
                <p class="mt-0.5 text-xs text-muted-foreground">
                    Isi rincian jenis makanan, porsi, tingkat urgensi, serta batas waktu penerimaan. Seluruh Donatur terverifikasi akan menerima notifikasi.
                </p>
            </div>

            <form method="POST" action="{{ route('recipient.requests.store') }}" class="p-5 sm:p-6">
                @csrf

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="judul" class="block text-sm font-medium text-foreground">Jenis / Nama Makanan yang Dibutuhkan</label>
                        <input
                            id="judul"
                            name="judul"
                            type="text"
                            value="{{ old('judul') }}"
                            placeholder="Contoh: Nasi Kotak Siap Santap untuk Makan Malam Anak Panti"
                            class="{{ $inputClasses }}"
                            required
                        />
                        <x-input-error :messages="$errors->get('judul')" class="mt-1.5" />
                    </div>

                    <div>
                        <label for="category_id" class="block text-sm font-medium text-foreground">Kategori Makanan</label>
                        <select id="category_id" name="category_id" class="{{ $inputClasses }}" required>
                            <option value="">Pilih kategori makanan...</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>
                                    {{ $category->nama_kategori }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('category_id')" class="mt-1.5" />
                    </div>

                    <div>
                        <label for="jumlah_porsi" class="block text-sm font-medium text-foreground">Estimasi Jumlah Porsi</label>
                        <input
                            id="jumlah_porsi"
                            name="jumlah_porsi"
                            type="number"
                            min="1"
                            max="5000"
                            value="{{ old('jumlah_porsi', 25) }}"
                            class="{{ $inputClasses }}"
                            required
                        />
                        <x-input-error :messages="$errors->get('jumlah_porsi')" class="mt-1.5" />
                    </div>

                    <div>
                        <label for="urgency_level" class="block text-sm font-medium text-foreground">Tingkat Urgensi</label>
                        <select id="urgency_level" name="urgency_level" class="{{ $inputClasses }}" required>
                            @foreach (\App\Enums\UrgencyLevel::cases() as $level)
                                <option value="{{ $level->value }}" @selected(old('urgency_level', 'mendesak') === $level->value)>
                                    {{ $level->label() }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('urgency_level')" class="mt-1.5" />
                    </div>

                    <div>
                        <label for="metode_penerimaan" class="block text-sm font-medium text-foreground">Preferensi Pengambilan / Pengantaran</label>
                        <select id="metode_penerimaan" name="metode_penerimaan" class="{{ $inputClasses }}" required>
                            <option value="fleksibel" @selected(old('metode_penerimaan', 'fleksibel') === 'fleksibel')>Fleksibel (Bisa Ambil Sendiri / Diantar)</option>
                            <option value="ambil_sendiri" @selected(old('metode_penerimaan') === 'ambil_sendiri')>Ambil Sendiri oleh Penerima</option>
                            <option value="diantar_kurir" @selected(old('metode_penerimaan') === 'diantar_kurir')>Diantar Donatur / Kurir</option>
                        </select>
                        <x-input-error :messages="$errors->get('metode_penerimaan')" class="mt-1.5" />
                    </div>

                    <div>
                        <label for="batas_waktu" class="block text-sm font-medium text-foreground">Batas Waktu Kebutuhan (Tenggat)</label>
                        <input
                            id="batas_waktu"
                            name="batas_waktu"
                            type="datetime-local"
                            value="{{ old('batas_waktu', now()->addHours(6)->format('Y-m-d\TH:i')) }}"
                            class="{{ $inputClasses }}"
                            required
                        />
                        <p class="mt-1 text-xs text-muted-foreground">Permintaan otomatis kedaluwarsa setelah melewati waktu ini.</p>
                        <x-input-error :messages="$errors->get('batas_waktu')" class="mt-1.5" />
                    </div>

                    <div>
                        <label for="deskripsi" class="block text-sm font-medium text-foreground">Keterangan Tambahan (Opsional)</label>
                        <textarea
                            id="deskripsi"
                            name="deskripsi"
                            rows="2"
                            maxlength="255"
                            placeholder="Contoh: Diutamakan makanan halal tanpa pedas untuk anak-anak usia 6-12 tahun."
                            class="{{ $inputClasses }}"
                        >{{ old('deskripsi') }}</textarea>
                        <x-input-error :messages="$errors->get('deskripsi')" class="mt-1.5" />
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3 border-t border-border pt-4">
                    <button
                        type="button"
                        @click="showCreateForm = false"
                        class="inline-flex items-center justify-center rounded-xl border border-border bg-background px-4 py-2 text-sm font-medium text-foreground transition hover:bg-accent"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        @disabled($quotaExhausted)
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <x-icon name="message-square" class="h-4 w-4" />
                        Siarkan ke Request Board
                    </button>
                </div>
            </form>
        </section>

        {{-- Daftar Permintaan Lembaga --}}
        <section class="overflow-hidden rounded-2xl border border-border bg-card" aria-labelledby="request-list-title">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border px-4 py-4 sm:px-6">
                <div>
                    <h2 id="request-list-title" class="text-sm font-semibold text-foreground">Daftar Permintaan Kebutuhan Lembaga Anda</h2>
                    <p class="mt-0.5 text-xs text-muted-foreground">Permintaan terbuka dan sedang diproses ditampilkan paling atas.</p>
                </div>

                @if ($activeFilter)
                    <a
                        href="{{ route('recipient.requests.index') }}"
                        class="inline-flex items-center gap-1.5 rounded-full border border-border bg-muted px-3 py-1 text-xs font-medium text-foreground transition hover:bg-accent"
                    >
                        Filter: {{ $stats[$activeFilter]['label'] ?? ucfirst($activeFilter) }}
                        <x-icon name="x" class="h-3 w-3 text-muted-foreground" />
                    </a>
                @endif
            </div>

            @if ($requests->isEmpty())
                <div class="p-6 sm:p-10">
                    <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border bg-background px-6 py-12 text-center">
                        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted text-muted-foreground">
                            <x-icon name="message-square" class="h-7 w-7" />
                        </span>
                        <h3 class="mt-4 text-sm font-semibold text-foreground">Belum ada permintaan kebutuhan makanan</h3>
                        <p class="mt-1.5 max-w-sm text-xs leading-5 text-muted-foreground">
                            Siarkan kebutuhan pangan mendesak lembaga Anda agar Donatur dapat melihat dan memenuhinya secara langsung.
                        </p>
                        @if (! $quotaExhausted)
                            <button
                                type="button"
                                @click="showCreateForm = true"
                                class="mt-5 inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground transition hover:bg-primary/90"
                            >
                                <x-icon name="plus" class="h-4 w-4" />
                                Buat Permintaan Pertama
                            </button>
                        @endif
                    </div>
                </div>
            @else
                <ul class="divide-y divide-border" role="list">
                    @foreach ($requests as $req)
                        @php
                            $urgency = $req->urgency_level instanceof \App\Enums\UrgencyLevel
                                ? $req->urgency_level
                                : \App\Enums\UrgencyLevel::tryFrom((string) $req->urgency_level) ?? \App\Enums\UrgencyLevel::Mendesak;
                            $statusEnum = $req->status instanceof \App\Enums\FoodRequestStatus
                                ? $req->status
                                : \App\Enums\FoodRequestStatus::tryFrom((string) $req->status) ?? \App\Enums\FoodRequestStatus::Open;
                        @endphp

                        <li class="p-4 transition-colors hover:bg-muted/30 sm:px-6 sm:py-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $urgency->badgeClass() }}">
                                            {{ $urgency->badgeLabel() }}
                                        </span>
                                        <x-status-badge :status="$statusEnum->value" />
                                        @if ($req->category)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium text-muted-foreground">
                                                <x-icon name="package" class="h-3 w-3" />
                                                {{ $req->category->nama_kategori }}
                                            </span>
                                        @endif
                                    </div>

                                    <h3 class="mt-2 text-base font-semibold text-foreground">{{ $req->judul }}</h3>

                                    @if ($req->deskripsi)
                                        <p class="mt-1 text-sm leading-6 text-muted-foreground">{{ $req->deskripsi }}</p>
                                    @endif

                                    <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-muted-foreground">
                                        <span class="inline-flex items-center gap-1.5 font-medium text-foreground">
                                            <x-icon name="utensils-crossed" class="h-3.5 w-3.5 text-primary" />
                                            {{ $req->jumlah_porsi }} porsi dibutuhkan
                                        </span>
                                        <span class="inline-flex items-center gap-1.5">
                                            <x-icon name="clock" class="h-3.5 w-3.5 text-secondary" />
                                            Batas waktu: {{ $req->batas_waktu->format('d/m/Y H:i') }}
                                            ({{ $req->batas_waktu->locale('id')->diffForHumans() }})
                                        </span>
                                        <span class="inline-flex items-center gap-1.5">
                                            <x-icon name="navigation" class="h-3.5 w-3.5" />
                                            {{ $req->metodePenerimaanLabel() }}
                                        </span>
                                    </div>

                                    @if ($req->donor)
                                        <div class="mt-3 flex flex-wrap items-center gap-3 rounded-xl border border-primary/20 bg-accent/60 px-3.5 py-2.5 text-xs">
                                            <span class="inline-flex items-center gap-1.5 font-semibold text-primary">
                                                <x-icon name="store" class="h-4 w-4" />
                                                Donatur Penyalur: {{ $req->donor->nama_usaha }}
                                            </span>

                                            @if ($req->claim)
                                                <span class="text-muted-foreground">
                                                    · Postingan: <strong class="text-foreground">{{ $req->claim->post?->judul ?? '-' }}</strong>
                                                    ({{ $req->claim->jumlah_porsi }} porsi)
                                                </span>
                                                @if ($req->claim->kode_verifikasi)
                                                    <span class="inline-flex items-center gap-1 rounded-md border border-border bg-white px-2 py-0.5 font-mono font-semibold text-foreground">
                                                        Kode Klaim: {{ $req->claim->kode_verifikasi }}
                                                    </span>
                                                @endif
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <div class="flex shrink-0 flex-wrap items-center gap-2 lg:justify-end">
                                    @if ($statusEnum === \App\Enums\FoodRequestStatus::InProgress)
                                        <form method="POST" action="{{ route('recipient.requests.complete', $req) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-1.5 rounded-lg bg-primary px-3.5 py-2 text-xs font-semibold text-primary-foreground transition hover:bg-primary/90"
                                            >
                                                <x-icon name="check" class="h-3.5 w-3.5" />
                                                Tandai Terpenuhi
                                            </button>
                                        </form>
                                    @endif

                                    @if ($statusEnum === \App\Enums\FoodRequestStatus::Open)
                                        <form
                                            method="POST"
                                            action="{{ route('recipient.requests.cancel', $req) }}"
                                            onsubmit="return confirm('Batalkan permintaan kebutuhan ini?');"
                                        >
                                            @csrf
                                            @method('PATCH')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-border bg-background px-3 py-2 text-xs font-semibold text-foreground transition hover:bg-accent"
                                            >
                                                <x-icon name="x" class="h-3.5 w-3.5" />
                                                Batalkan
                                            </button>
                                        </form>
                                    @endif

                                    @if (! in_array($statusEnum, [\App\Enums\FoodRequestStatus::InProgress, \App\Enums\FoodRequestStatus::Fulfilled], true))
                                        <form
                                            method="POST"
                                            action="{{ route('recipient.requests.destroy', $req) }}"
                                            onsubmit="return confirm('Hapus permintaan kebutuhan ini secara permanen?');"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-[#FCA5A5] bg-card px-3 py-2 text-xs font-semibold text-[#B91C1C] transition hover:bg-[#FEE2E2]"
                                            >
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                @if ($requests->hasPages())
                    <div class="border-t border-border px-4 py-4 sm:px-6">
                        {{ $requests->onEachSide(1)->links() }}
                    </div>
                @endif
            @endif
        </section>
    </div>
</x-app-layout>
