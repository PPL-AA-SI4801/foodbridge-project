{{-- PBI-23: bukti foto serah terima setelah klaim Selesai (hanya Donor pemilik). --}}
<div class="mt-4 rounded-xl border border-border bg-card p-4" dusk="courier-handover-photo-{{ $claim->id }}">
    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
        Bukti serah terima kurir
    </p>
    <p class="mt-1 text-[11px] text-muted-foreground">
        Diserahkan{{ $claim->completed_at ? ' pada '.$claim->completed_at->format('d/m/Y H:i') : '' }}. Foto tidak dapat diubah.
    </p>
    <a href="{{ asset('storage/'.$claim->courierDetail->foto_serah_terima) }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-block overflow-hidden rounded-lg border border-border hover:opacity-90 transition">
        <img src="{{ asset('storage/'.$claim->courierDetail->foto_serah_terima) }}" alt="Foto kurir serah terima" class="max-h-48 w-auto object-cover" />
    </a>
</div>
