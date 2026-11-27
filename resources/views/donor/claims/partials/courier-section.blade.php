{{-- Data pengemudi kurir read-only (PBI-11) + bukti serah terima PBI-23. --}}
<div class="mt-5 rounded-xl border border-border bg-card p-4" dusk="donor-courier-details-{{ $claim->id }}">
    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
        Data Pengemudi Kurir
    </p>
    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
        <div>
            <dt class="text-xs text-muted-foreground">Platform</dt>
            <dd class="font-medium text-foreground">{{ $claim->courierDetail->platform }}</dd>
        </div>
        <div>
            <dt class="text-xs text-muted-foreground">Nama pengemudi</dt>
            <dd class="font-medium text-foreground">{{ $claim->courierDetail->nama_pengemudi }}</dd>
        </div>
        <div>
            <dt class="text-xs text-muted-foreground">Nomor kendaraan</dt>
            <dd class="font-medium text-foreground">{{ $claim->courierDetail->no_kendaraan }}</dd>
        </div>
        <div>
            <dt class="text-xs text-muted-foreground">Kode verifikasi kurir</dt>
            <dd class="font-mono font-medium text-foreground">{{ $claim->courierDetail->kode_verifikasi_kurir }}</dd>
        </div>
    </dl>

    @if ($claim->courierDetail->foto_bukti)
        <div class="mt-3 border-t border-border/60 pt-2.5">
            <dt class="text-xs text-muted-foreground">Tangkapan layar pesanan kurir:</dt>
            <dd class="mt-1.5">
                <a href="{{ asset('storage/'.$claim->courierDetail->foto_bukti) }}" target="_blank" rel="noopener noreferrer" class="inline-block overflow-hidden rounded-lg border border-border hover:opacity-90 transition">
                    <img src="{{ asset('storage/'.$claim->courierDetail->foto_bukti) }}" alt="Tangkapan layar pesanan" class="max-h-48 w-auto object-cover" />
                </a>
            </dd>
        </div>
    @endif
</div>

{{-- PBI-23: unggah foto kurir + tombol serah terima (hanya saat Disetujui), bukti foto setelah Selesai --}}
@if ($statusKey === 'disetujui')
    @include('donor.claims.partials.courier-handover-form', ['claim' => $claim])
@elseif ($statusKey === 'selesai' && $claim->courierDetail->foto_serah_terima)
    @include('donor.claims.partials.courier-handover-photo', ['claim' => $claim])
@endif
