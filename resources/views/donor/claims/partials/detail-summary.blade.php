{{-- Ringkasan atribut klaim: porsi, tanggal, metode, status. --}}
<dl class="mt-5 grid gap-4 sm:grid-cols-2">
    <div>
        <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Jumlah porsi</dt>
        <dd class="mt-1 text-sm font-medium text-foreground">{{ $claim->jumlah_porsi }} porsi</dd>
    </div>
    <div>
        <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Tanggal klaim</dt>
        <dd class="mt-1 text-sm font-medium text-foreground">{{ $claim->tanggal_klaim->format('d/m/Y') }}</dd>
    </div>
    <div>
        <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Metode pengambilan</dt>
        <dd class="mt-1 text-sm font-medium text-foreground">
            @if (in_array($claim->metode_pengambilan, ['kurir_online', 'kurir']))
                Kurir Online
            @elseif (in_array($claim->metode_pengambilan, ['ambil_sendiri']))
                Ambil Sendiri
            @else
                Belum dipilih
            @endif
        </dd>
    </div>
    <div>
        <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Status klaim</dt>
        <dd class="mt-1"><x-status-badge :status="$statusKey" /></dd>
    </div>
</dl>
