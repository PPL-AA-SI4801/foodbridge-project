<x-guest-layout width="lg">
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-muted-foreground">
            Verifikasi Akun
        </p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-foreground">
            Lengkapi Dokumen
        </h1>
        <p class="mt-2 text-sm leading-6 text-muted-foreground">
            Administrator meminta Anda melengkapi atau mengganti dokumen legalitas berikut.
            Setelah semua dokumen diperbarui, akun Anda akan kembali menunggu pemeriksaan administrator.
        </p>
    </div>

    <x-auth-session-status class="mb-5" :status="session('status')" />

    @if ($errors->any())
        <div role="alert" class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-medium">Dokumen belum dapat dikirim.</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-6 rounded-md border border-border bg-muted px-4 py-3 text-sm">
        <p class="font-medium">{{ $user->nama }}</p>
        <p class="mt-0.5 text-muted-foreground">{{ $user->email }}</p>
    </div>

    @forelse ($documents as $document)
        <section class="border-t border-border py-5 first:border-t-0 first:pt-0 last:pb-0" aria-labelledby="document-title-{{ $document->id }}">
            <div class="flex flex-wrap items-center gap-2">
                <h2 id="document-title-{{ $document->id }}" class="text-sm font-semibold text-foreground">
                    {{ $document->jenisDokumenLabel() }}
                </h2>
                <x-status-badge :status="$document->status" />
            </div>

            @if ($document->catatan)
                <p class="mt-2 rounded-md border border-[#FDBA74] bg-[#FFEDD5] px-3 py-2 text-sm text-[#9A3412]">
                    <span class="font-medium">Catatan administrator:</span>
                    {{ $document->catatan }}
                </p>
            @endif

            <form
                method="POST"
                action="{{ route('documents.completeness.update', $document) }}"
                enctype="multipart/form-data"
                class="mt-4 space-y-3"
                dusk="completeness-form-{{ $document->id }}"
            >
                @csrf

                <div>
                    <label for="dokumen-{{ $document->id }}" class="block text-sm font-medium text-foreground">
                        Unggah dokumen pengganti
                    </label>
                    <input
                        id="dokumen-{{ $document->id }}"
                        name="dokumen"
                        type="file"
                        accept=".pdf,.jpg,.jpeg,.png"
                        required
                        dusk="completeness-dokumen-{{ $document->id }}"
                        class="mt-2 block w-full rounded-md border border-input bg-card px-3 py-2 text-sm text-foreground file:mr-3 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-primary-foreground hover:file:opacity-90 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                    >
                    <p class="mt-1 text-xs text-muted-foreground">PDF, JPG, JPEG, atau PNG. Maksimal 2 MB.</p>
                </div>

                <button
                    type="submit"
                    dusk="completeness-submit-{{ $document->id }}"
                    class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground transition-opacity hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                >
                    <x-icon name="upload" class="h-4 w-4" />
                    Unggah Dokumen
                </button>
            </form>
        </section>
    @empty
        <div class="rounded-md border border-border bg-muted p-4 text-sm text-muted-foreground">
            Tidak ada dokumen yang perlu dilengkapi saat ini.
        </div>
    @endforelse

    <div class="mt-8 border-t border-border pt-5">
        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button
                type="submit"
                dusk="completeness-logout"
                class="inline-flex items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            >
                <x-icon name="log-out" class="h-4 w-4" />
                Keluar dari akun
            </button>
        </form>
    </div>
</x-guest-layout>
