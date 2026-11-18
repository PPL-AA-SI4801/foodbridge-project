{{-- Verification status card shown on the sign-in page while the registered
     account is still awaiting verification. Visibility is driven by the
     persistent `pending_verification_email` session key (survives refresh),
     not by flashed status text. Indeterminate progress: verification time
     is unknown, so the bar slides instead of filling. --}}
<style>
@keyframes vsb-slide {
    from { transform: translateX(-110%); }
    to { transform: translateX(320%); }
}
@keyframes vsb-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.35; }
}
.vsb-bar {
    animation: vsb-slide 1.4s linear infinite;
    will-change: transform;
}
.vsb-dot {
    animation: vsb-pulse 1.6s ease-in-out infinite;
}
@media (prefers-reduced-motion: reduce) {
    .vsb-bar, .vsb-dot { animation: none; }
}
</style>
<div class="mb-5 rounded-lg border border-border bg-white p-5 shadow-sm" data-vsb-card data-current="{{ $statusAkun ?? 'menunggu' }}:{{ ! empty($needsCompleteness) ? '1' : '0' }}">
    <div class="flex items-center justify-between gap-3">
        <h2 class="text-base font-semibold text-foreground">Status verifikasi akun</h2>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
            <span class="vsb-dot h-1.5 w-1.5 rounded-full bg-blue-600"></span>Memverifikasi
        </span>
    </div>
    @if (! empty($isReverification))
    <p class="mt-1 text-sm text-muted-foreground">Dokumen Anda sedang diverifikasi ulang.</p>
    @else
    <p class="mt-1 text-sm text-muted-foreground">Pendaftaran berhasil. Lengkapi dokumen Anda agar proses verifikasi bisa dilanjutkan.</p>
    @endif

    <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-muted" role="progressbar" aria-label="Verifikasi sedang berjalan">
        <div class="vsb-bar h-full w-1/3 rounded-full bg-primary"></div>
    </div>

    @if (! empty($needsCompleteness))
    <div class="mt-4 border-t border-border pt-4">
        <h3 class="text-sm font-semibold text-foreground">Lengkapi Dokumen</h3>
        <p class="mt-0.5 text-xs text-muted-foreground">Pilih jenis dokumen lalu unggah berkasnya.</p>

        @if (! empty($completenessNote))
            <p class="mt-2 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                <span class="font-medium">Catatan administrator:</span> {{ $completenessNote }}
            </p>
        @endif

        @if ($errors->has('jenis_dokumen') || $errors->has('dokumen'))
            <p class="mt-2 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700" role="alert">
                {{ $errors->first('jenis_dokumen', $errors->first('dokumen')) }}
            </p>
        @endif

        <form action="{{ route('verifikasi.dokumen.store') }}" method="POST" enctype="multipart/form-data" class="mt-3 space-y-3" dusk="lengkapi-form">
            @csrf
            <div>
                <label for="lengkapi-jenis" class="block text-sm font-medium text-foreground">Jenis dokumen</label>
                <select id="lengkapi-jenis" name="jenis_dokumen" dusk="lengkapi-jenis"
                        class="mt-1.5 block w-full rounded-md border border-input bg-white px-3 py-2 text-sm text-foreground shadow-none focus:border-primary focus:ring-1 focus:ring-primary">
                    <option value="nib">NIB</option>
                    <option value="akta_pendirian">Akta Pendirian</option>
                    <option value="izin_operasional">Izin Operasional</option>
                    <option value="lainnya">Dokumen Lainnya</option>
                </select>
            </div>

            <div>
                <label for="lengkapi-berkas" class="block text-sm font-medium text-foreground">Berkas dokumen</label>
                <input id="lengkapi-berkas" type="file" name="dokumen" accept=".pdf,.jpg,.jpeg,.png" dusk="lengkapi-berkas"
                       class="mt-1.5 block w-full text-sm text-muted-foreground file:mr-3 file:rounded-md file:border-0 file:bg-muted file:px-3 file:py-2 file:text-sm file:font-medium file:text-foreground hover:file:bg-border" />
                <p class="mt-1 text-[11px] text-muted-foreground">PDF, JPG, atau PNG. Maks. 2 MB.</p>
            </div>

            <x-button type="submit" size="md" class="btn-block btn-secondary" dusk="lengkapi-submit">
                <x-slot:icon>
                    <x-icon name="upload" />
                </x-slot:icon>
                {{ __('Unggah Dokumen') }}
            </x-button>
        </form>
    </div>
    @endif

    <p class="mt-4 text-xs text-muted-foreground">* Verifikasi maksimal 1&times;24 jam.</p>
</div>

<script>
(function () {
    var card = document.querySelector('[data-vsb-card]');
    if (!card) return;
    var initial = card.getAttribute('data-current');
    setInterval(function () {
        if (document.hidden) return;
        fetch("{{ route('verifikasi.status') }}", { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (!data) return;
                if (data.tracked && data.status_akun === 'aktif') {
                    window.location.href = "{{ route('verifikasi.masuk') }}";
                    return;
                }
                var current = data.tracked ? (data.status_akun + ':' + (data.needs_completeness ? '1' : '0')) : 'gone';
                if (current !== initial) window.location.reload();
            })
            .catch(function () {});
    }, 10000);
})();
</script>
