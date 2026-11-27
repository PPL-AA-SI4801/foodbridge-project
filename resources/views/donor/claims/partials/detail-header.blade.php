{{-- Header modal rincian klaim Donor: judul, penerima + kontak, badge status. --}}
<div class="flex items-start justify-between gap-4">
    <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-primary">Rincian Klaim</p>
        <h3 class="mt-1.5 truncate text-lg font-semibold text-foreground">
            {{ $post?->judul ?? 'Postingan tidak tersedia' }}
        </h3>
        <p class="mt-1 flex flex-wrap items-center gap-1.5 text-sm text-muted-foreground">
            <span>Penerima:</span>
            <strong class="font-medium text-foreground">{{ $recipient?->nama_lembaga ?? 'Lembaga Penerima' }}</strong>
            @if ($recipient?->user?->no_hp)
                <span class="text-muted-foreground/60">•</span>
                <a href="tel:{{ $recipient->user->no_hp }}" class="inline-flex items-center gap-1 font-mono text-xs font-semibold text-primary hover:underline">
                    <x-icon name="phone" class="h-3 w-3" />
                    <span>{{ $recipient->user->no_hp }}</span>
                </a>
            @endif
        </p>
    </div>

    <x-status-badge :status="$statusKey" class="shrink-0" />
</div>
