{{-- Metode Ambil Sendiri: info + form kode verifikasi PBI-12 (hanya saat Disetujui). --}}
<div class="mt-5 rounded-xl border border-border bg-background px-4 py-3 text-sm text-muted-foreground">
    <p class="flex items-center gap-2">
        <x-icon name="check" class="h-4 w-4 text-primary" />
        <span>Penerima memilih untuk mengambil makanan secara mandiri ke lokasi Anda.</span>
    </p>
</div>

{{-- PBI-12: validasi kode + tombol serah terima (hanya saat Disetujui) --}}
@if ($statusKey === 'disetujui')
    <form
        method="POST"
        action="{{ route('donor.claims.complete', $claim) }}"
        class="mt-4 rounded-xl border border-primary/25 bg-primary/[0.03] p-4"
        dusk="complete-claim-form-{{ $claim->id }}"
    >
        @csrf
        @method('PATCH')

        <label for="kode_verifikasi-{{ $claim->id }}" class="block text-xs font-semibold text-foreground">
            Kode verifikasi dari penerima
        </label>
        <div class="mt-2 flex flex-col gap-2 sm:flex-row">
            <input
                type="text"
                name="kode_verifikasi"
                id="kode_verifikasi-{{ $claim->id }}"
                dusk="kode-verifikasi-{{ $claim->id }}"
                inputmode="numeric"
                maxlength="6"
                placeholder="6 digit angka"
                class="h-10 w-full rounded-lg border border-input bg-background px-3 font-mono text-sm tracking-[0.2em] text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 sm:max-w-56"
            />
            <button
                type="submit"
                dusk="complete-claim-{{ $claim->id }}"
                class="inline-flex h-10 items-center justify-center gap-1.5 rounded-lg bg-primary px-4 text-xs font-semibold text-primary-foreground transition hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/30"
            >
                <x-icon name="check" class="h-3.5 w-3.5" />
                Makanan sudah diserahkan
            </button>
        </div>
        <p class="mt-2 text-[11px] text-muted-foreground">
            Masukkan 6 digit kode yang ditunjukkan penerima, lalu tekan tombol setelah makanan diserahkan.
        </p>
        @if ($errors->has('kode_verifikasi'))
            <p class="mt-2 text-xs font-medium text-[#B91C1C]" dusk="complete-claim-errors-{{ $claim->id }}">{{ $errors->first('kode_verifikasi') }}</p>
        @endif
    </form>
@endif
