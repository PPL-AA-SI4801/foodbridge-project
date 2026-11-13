@php
    $inputClasses = 'mt-1.5 block w-full rounded-lg border border-input bg-white px-3 py-2 text-sm text-foreground shadow-xs transition focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20';
    $labelClasses = 'block text-sm font-medium text-foreground';
    $errorClasses = 'mt-1 text-xs text-destructive';
@endphp

<x-app-layout padded title="Buat Klaim" :pending-verifications="$pendingVerifications">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Buat Klaim</h1>
        <p class="mt-1 text-sm text-muted-foreground">
            Ajukan klaim atas nama penerima. Klaim baru berstatus menunggu keputusan donor.
        </p>
    </div>

    <x-flash />

    <form method="POST" action="{{ route('admin.claims.store') }}" class="max-w-3xl space-y-6">
        @csrf

        <div class="rounded-xl border border-border bg-card p-5">
            <div class="space-y-4">
                <div>
                    <label for="recipient_id" class="{{ $labelClasses }}">Penerima</label>
                    <select id="recipient_id" name="recipient_id" required class="{{ $inputClasses }}">
                        <option value="">Pilih penerima</option>
                        @foreach ($recipients as $recipient)
                            <option value="{{ $recipient->id }}" @selected((string) old('recipient_id') === (string) $recipient->id)>
                                {{ $recipient->nama_lembaga }} — {{ $recipient->user?->nama }}
                            </option>
                        @endforeach
                    </select>
                    @error('recipient_id')
                        <p class="{{ $errorClasses }}">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="post_id" class="{{ $labelClasses }}">Postingan Makanan</label>
                    <select id="post_id" name="post_id" required class="{{ $inputClasses }}">
                        <option value="">Pilih postingan yang masih tersedia</option>
                        @foreach ($foodPosts as $foodPost)
                            <option value="{{ $foodPost->id }}" @selected((string) old('post_id') === (string) $foodPost->id)>
                                {{ $foodPost->judul }} — {{ $foodPost->donor?->nama_usaha }} — sisa {{ $foodPost->sisa_porsi }} porsi — batas {{ $foodPost->batas_konsumsi?->format('d/m/Y H:i') }}
                            </option>
                        @endforeach
                    </select>
                    @if ($foodPosts->isEmpty())
                        <p class="mt-1 text-xs text-muted-foreground">
                            Belum ada postingan yang tersedia. Klaim hanya dapat dibuat untuk makanan yang belum kedaluwarsa dan masih memiliki sisa porsi.
                        </p>
                    @endif
                    @error('post_id')
                        <p class="{{ $errorClasses }}">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="jumlah_porsi" class="{{ $labelClasses }}">Jumlah Porsi</label>
                    <input
                        id="jumlah_porsi"
                        name="jumlah_porsi"
                        type="number"
                        min="1"
                        value="{{ old('jumlah_porsi', 1) }}"
                        required
                        class="{{ $inputClasses }}"
                    />
                    <p class="mt-1 text-xs text-muted-foreground">Jumlah porsi tidak boleh melebihi sisa porsi postingan.</p>
                    @error('jumlah_porsi')
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
                Simpan Klaim
            </button>

            <a
                href="{{ route('admin.claims.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-input bg-card px-4 py-2 text-sm font-medium transition-colors hover:bg-accent"
            >
                Batal
            </a>
        </div>
    </form>
</x-app-layout>
