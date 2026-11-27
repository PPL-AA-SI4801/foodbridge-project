{{-- PBI-23: form unggah foto kurir + tombol serah terima (hanya saat Disetujui). --}}
<form
    method="POST"
    action="{{ route('donor.claims.complete_courier', $claim) }}"
    enctype="multipart/form-data"
    class="mt-4 rounded-xl border border-primary/25 bg-primary/[0.03] p-4"
    dusk="courier-handover-form-{{ $claim->id }}"
>
    @csrf
    @method('PATCH')

    <label for="foto-serah-terima-{{ $claim->id }}" class="block text-xs font-semibold text-foreground">
        Foto kurir sebagai bukti serah terima
    </label>
    <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center">
        <input
            type="file"
            name="foto_serah_terima"
            id="foto-serah-terima-{{ $claim->id }}"
            dusk="foto-serah-terima-{{ $claim->id }}"
            accept=".jpg,.jpeg,.png,.webp"
            class="block w-full text-xs text-muted-foreground file:mr-3 file:rounded-lg file:border file:border-input file:bg-background file:px-3 file:py-2 file:text-xs file:font-semibold file:text-foreground hover:file:bg-accent"
        />
        <button
            type="submit"
            dusk="complete-courier-{{ $claim->id }}"
            class="inline-flex h-10 shrink-0 items-center justify-center gap-1.5 rounded-lg bg-primary px-4 text-xs font-semibold text-primary-foreground transition hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/30"
        >
            <x-icon name="check" class="h-3.5 w-3.5" />
            Makanan sudah diserahkan
        </button>
    </div>
    <p class="mt-2 text-[11px] text-muted-foreground">
        Cocokkan nama, nomor kendaraan, dan kode verifikasi dengan kurir yang datang, unggah foto kurir (JPG/JPEG/PNG/WEBP, maks 2 MB), lalu tekan tombol setelah makanan diserahkan.
    </p>
    @if ($errors->has('foto_serah_terima'))
        <p class="mt-2 text-xs font-medium text-[#B91C1C]" dusk="courier-handover-errors-{{ $claim->id }}">{{ $errors->first('foto_serah_terima') }}</p>
    @endif
</form>
