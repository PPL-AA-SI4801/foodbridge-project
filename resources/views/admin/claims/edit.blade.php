@php
    $inputClasses = 'mt-1.5 block w-full rounded-lg border border-input bg-white px-3 py-2 text-sm text-foreground shadow-xs transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20';
    $labelClasses = 'block text-sm font-medium text-foreground';
    $errorClasses = 'mt-1 text-xs text-destructive';
    $isPending = in_array($claim->status, \App\Support\ClaimStatus::GROUPS['menunggu'], true);
    $normalizedStatus = \App\Support\ClaimStatus::normalize($claim->status);
@endphp

<x-app-layout padded title="Ubah Klaim" :pending-verifications="$pendingVerifications">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Ubah Klaim</h1>
        <p class="mt-1 text-sm text-muted-foreground">
            Tinjau klaim untuk makanan "{{ $claim->post?->judul }}" dari {{ $claim->recipient?->nama_lembaga }}.
        </p>
    </div>

    <x-flash />

    <div class="mb-6 grid gap-4 rounded-xl border border-border bg-card p-5 sm:grid-cols-2">
        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Makanan</p>
            <p class="mt-1 font-medium">{{ $claim->post?->judul ?? '—' }}</p>
            <p class="text-sm text-muted-foreground">{{ $claim->post?->donor?->nama_usaha }}</p>
        </div>

        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Penerima</p>
            <p class="mt-1 font-medium">{{ $claim->recipient?->nama_lembaga ?? '—' }}</p>
            <p class="text-sm text-muted-foreground">{{ $claim->recipient?->user?->nama }}</p>
        </div>

        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Tanggal Klaim</p>
            <p class="mt-1 font-medium">{{ $claim->tanggal_klaim?->format('d/m/Y') ?? '—' }}</p>
        </div>

        <div>
            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Status</p>
            <div class="mt-1">
                <x-status-badge :status="$normalizedStatus" />
            </div>
        </div>
    </div>

    @if (! $isPending)
        <div class="rounded-lg border border-border bg-muted/50 p-5">
            <p class="text-sm text-muted-foreground">
                Klaim ini sudah diproses sehingga jumlah porsi dan statusnya tidak dapat diubah lagi.
            </p>
            <a
                href="{{ route('admin.claims.index') }}"
                class="mt-4 inline-flex items-center justify-center rounded-lg border border-input bg-card px-4 py-2 text-sm font-medium transition-colors hover:bg-accent"
            >
                Kembali ke Daftar Klaim
            </a>
        </div>
    @else
        <form method="POST" action="{{ route('admin.claims.update', $claim) }}" class="max-w-3xl space-y-6">
            @csrf
            @method('PUT')

            <div class="rounded-xl border border-border bg-card p-5">
                <div class="space-y-4">
                    <div>
                        <label for="jumlah_porsi" class="{{ $labelClasses }}">Jumlah Porsi</label>
                        <input
                            id="jumlah_porsi"
                            name="jumlah_porsi"
                            type="number"
                            min="1"
                            value="{{ old('jumlah_porsi', $claim->jumlah_porsi) }}"
                            required
                            class="{{ $inputClasses }}"
                        />
                        <p class="mt-1 text-xs text-muted-foreground">
                            Sisa porsi postingan saat ini: {{ $claim->post?->sisa_porsi ?? 0 }} porsi.
                        </p>
                        @error('jumlah_porsi')
                            <p class="{{ $errorClasses }}">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="status" class="{{ $labelClasses }}">Keputusan</label>
                        <select id="status" name="status" required class="{{ $inputClasses }}">
                            <option value="pending" @selected(old('status', $claim->status) === 'pending')>
                                Tetap menunggu
                            </option>
                            <option value="approved" @selected(old('status', $claim->status) === 'approved')>
                                Setujui klaim
                            </option>
                            <option value="rejected" @selected(old('status', $claim->status) === 'rejected')>
                                Tolak klaim
                            </option>
                        </select>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Menyetujui klaim akan mengurangi sisa porsi dan mengirim notifikasi ke penerima.
                        </p>
                        @error('status')
                            <p class="{{ $errorClasses }}">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90"
                >
                    <x-icon name="check" class="h-4 w-4" />
                    Simpan Perubahan
                </button>

                <a
                    href="{{ route('admin.claims.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-input bg-card px-4 py-2 text-sm font-medium transition-colors hover:bg-accent"
                >
                    Batal
                </a>
            </div>
        </form>
    @endif
</x-app-layout>
