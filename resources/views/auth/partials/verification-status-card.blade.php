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
<div class="mb-5 rounded-lg border border-border bg-white p-5 shadow-sm">
    <div class="flex items-center justify-between gap-3">
        <h2 class="text-base font-semibold text-foreground">Status verifikasi akun</h2>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
            <span class="vsb-dot h-1.5 w-1.5 rounded-full bg-blue-600"></span>Memverifikasi
        </span>
    </div>
    <p class="mt-1 text-sm text-muted-foreground">Pendaftaran berhasil. Akun Anda sedang menunggu verifikasi administrator.</p>

    <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-muted" role="progressbar" aria-label="Verifikasi sedang berjalan">
        <div class="vsb-bar h-full w-1/3 rounded-full bg-primary"></div>
    </div>

    <p class="mt-1 text-xs text-muted-foreground">* Verifikasi maksimal 1&times;24 jam.</p>
</div>
