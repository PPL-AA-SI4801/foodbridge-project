@php
    $inputClasses = 'mt-1.5 block w-full rounded-lg border border-input bg-white px-3 py-2 text-sm text-foreground shadow-xs transition placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20';
    $labelClasses = 'block text-sm font-medium text-foreground';
    $errorClasses = 'mt-1 text-xs text-destructive';
@endphp

<x-app-layout padded title="Buat Kategori Gizi" :pending-verifications="$pendingVerifications">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Buat Kategori Gizi</h1>
        <p class="mt-1 text-sm text-muted-foreground">
            Tambahkan kategori baru untuk mengelompokkan postingan makanan.
        </p>
    </div>

    <x-flash />

    <form method="POST" action="{{ route('admin.categories.store') }}" class="max-w-xl space-y-6">
        @csrf

        <div class="rounded-xl border border-border bg-card p-5">
            <label for="nama_kategori" class="{{ $labelClasses }}">Nama Kategori</label>
            <input
                id="nama_kategori"
                name="nama_kategori"
                type="text"
                value="{{ old('nama_kategori') }}"
                placeholder="Contoh: Karbohidrat"
                required
                class="{{ $inputClasses }}"
            />
            @error('nama_kategori')
                <p class="{{ $errorClasses }}">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button
                type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90"
            >
                <x-icon name="check" class="h-4 w-4" />
                Simpan Kategori
            </button>

            <a
                href="{{ route('admin.categories.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-input bg-card px-4 py-2 text-sm font-medium transition-colors hover:bg-accent"
            >
                Batal
            </a>
        </div>
    </form>
</x-app-layout>
