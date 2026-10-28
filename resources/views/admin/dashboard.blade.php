<x-admin-layout title="Dashboard" :pending-verifications="$pendingVerifications">
    <div class="grid gap-6 md:grid-cols-2">
        <div class="rounded-lg border border-border bg-card p-6">
            <p class="text-sm font-medium">Area administrator</p>
            <p class="mt-1 text-sm text-muted-foreground">
                Verifikasi akun, laporan makanan, dan manajemen data tersedia pada menu di samping.
            </p>
        </div>

        <a
            href="{{ route('admin.verifications.index') }}"
            class="group rounded-lg border border-border bg-card p-6 transition-colors hover:bg-accent/40 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
        >
            <div class="flex items-start justify-between gap-4">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-muted text-foreground">
                    <x-icon name="user-check" class="h-5 w-5" />
                </span>

                <span class="inline-flex h-7 min-w-7 items-center justify-center rounded-full bg-secondary px-2 text-sm font-semibold text-white">
                    {{ $pendingVerifications }}
                </span>
            </div>

            <p class="mt-4 text-sm font-medium">Verifikasi Akun</p>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ $pendingVerifications }} pendaftaran Donor dan Penerima menunggu verifikasi.
            </p>

            <span class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-primary">
                Tinjau antrean
                <x-icon name="chevron-right" class="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
            </span>
        </a>
    </div>
</x-admin-layout>
