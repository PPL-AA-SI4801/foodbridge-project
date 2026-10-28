<x-admin-layout title="Detail Verifikasi" :pending-verifications="$pendingVerifications">
    @php
        $isPending = $user->status_akun === \App\Enums\AccountStatus::Menunggu;
        $hasDocument = $user->verificationDocuments->isNotEmpty();
        $profile = $user->role === \App\Enums\Role::Donor
            ? $user->donorProfile
            : $user->recipientProfile;
        $profileType = $user->role === \App\Enums\Role::Donor
            ? $profile?->jenis_usaha
            : $profile?->jenis_lembaga;
        $profileNameLabel = $user->role === \App\Enums\Role::Donor ? 'Nama usaha' : 'Nama lembaga';
        $profileTypeLabel = $user->role === \App\Enums\Role::Donor ? 'Jenis usaha' : 'Jenis lembaga';
    @endphp

    <div class="mb-6">
        <a
            href="{{ route('admin.verifications.index') }}"
            class="inline-flex items-center gap-1 text-sm text-muted-foreground transition-colors hover:text-foreground"
        >
            <x-icon name="chevron-right" class="h-4 w-4 rotate-180" />
            Kembali ke daftar
        </a>

        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Detail Verifikasi</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ $user->nama }} &middot; {{ ucfirst($user->role->value) }}
                </p>
            </div>

            <x-status-badge :status="$user->status_akun" />
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="flex flex-col gap-6 lg:col-span-2">
            <section class="rounded-lg border border-border bg-card">
                <div class="border-b border-border px-6 py-4">
                    <h2 class="text-base font-semibold">Identitas Pendaftar</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Data akun dan profil organisasi.</p>
                </div>

                <dl class="grid gap-5 p-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Nama akun</dt>
                        <dd class="mt-1 text-sm font-medium">{{ $user->nama }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Peran</dt>
                        <dd class="mt-1 text-sm font-medium">{{ ucfirst($user->role->value) }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Email</dt>
                        <dd class="mt-1 text-sm">{{ $user->email }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Nomor HP</dt>
                        <dd class="mt-1 text-sm">{{ $user->no_hp }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ $profileNameLabel }}</dt>
                        <dd class="mt-1 text-sm font-medium">{{ $user->profileName() ?? '—' }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">{{ $profileTypeLabel }}</dt>
                        <dd class="mt-1 text-sm">{{ $profileType ?? '—' }}</dd>
                    </div>

                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Alamat</dt>
                        <dd class="mt-1 text-sm">{{ $profile?->alamat ?? '—' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-lg border border-border bg-card">
                <div class="border-b border-border px-6 py-4">
                    <h2 class="text-base font-semibold">Dokumen Legalitas</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Dokumen yang diunggah saat pendaftaran.</p>
                </div>

                @forelse ($user->verificationDocuments as $document)
                    <div class="flex flex-col gap-4 border-b border-border p-6 last:border-b-0 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-medium">{{ $document->jenisDokumenLabel() }}</p>
                                <x-status-badge :status="$document->status" />
                            </div>

                            @if ($document->verified_at)
                                <p class="mt-2 text-xs text-muted-foreground">
                                    Diverifikasi {{ $document->verified_at->locale('id')->translatedFormat('d F Y, H:i') }}
                                </p>
                            @endif

                            @if ($document->catatan)
                                <p class="mt-2 rounded-md border border-border bg-muted px-3 py-2 text-sm">
                                    <span class="font-medium">Catatan:</span> {{ $document->catatan }}
                                </p>
                            @endif
                        </div>

                        <a
                            href="{{ route('admin.documents.show', $document) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex w-fit shrink-0 items-center gap-2 rounded-md border border-input bg-card px-3 py-2 text-sm font-medium transition-colors hover:bg-accent focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                        >
                            Lihat Dokumen
                            <x-icon name="chevron-right" class="h-4 w-4" />
                        </a>
                    </div>
                @empty
                    <div class="p-6">
                        <div class="flex items-start gap-3 rounded-md border border-[#FDE047] bg-[#FEF9C3] p-4 text-sm text-[#854D0E]">
                            <x-icon name="triangle-alert" class="mt-0.5 h-4 w-4 shrink-0" />
                            <div>
                                <p class="font-medium">Belum ada dokumen legalitas</p>
                                <p class="mt-1">Pendaftar belum mengunggah dokumen yang dapat ditinjau.</p>
                            </div>
                        </div>
                    </div>
                @endforelse
            </section>
        </div>

        <aside class="lg:col-span-1">
            <section class="rounded-lg border border-border bg-card">
                <div class="border-b border-border px-6 py-4">
                    <h2 class="text-base font-semibold">Keputusan Verifikasi</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Setujui atau tolak pendaftaran ini.
                    </p>
                </div>

                <div class="p-6">
                    @if (! $isPending)
                        <div class="rounded-md border border-border bg-muted p-4 text-sm">
                            <p class="font-medium">Pendaftaran sudah diproses</p>
                            <p class="mt-1 text-muted-foreground">
                                Status akun ini adalah
                                <span class="font-medium text-foreground">{{ ucfirst($user->status_akun->value) }}</span>.
                                Keputusan tidak dapat diubah dari halaman ini.
                            </p>
                        </div>
                    @elseif ($hasDocument)
                        <form method="POST" action="{{ route('admin.verifications.approve', $user) }}">
                            @csrf

                            <button
                                type="submit"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground transition-opacity hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                            >
                                <x-icon name="user-check" class="h-4 w-4" />
                                Setujui Pendaftaran
                            </button>
                        </form>

                        <div class="my-6 flex items-center gap-3">
                            <div class="h-px flex-1 bg-border"></div>
                            <span class="text-xs font-medium uppercase tracking-wide text-muted-foreground">atau</span>
                            <div class="h-px flex-1 bg-border"></div>
                        </div>

                        <form method="POST" action="{{ route('admin.verifications.reject', $user) }}">
                            @csrf

                            <label for="catatan" class="block text-sm font-medium">
                                Alasan penolakan <span class="text-destructive">*</span>
                            </label>

                            <textarea
                                id="catatan"
                                name="catatan"
                                rows="4"
                                required
                                maxlength="255"
                                aria-describedby="catatan-help{{ $errors->has('catatan') ? ' catatan-error' : '' }}"
                                aria-invalid="{{ $errors->has('catatan') ? 'true' : 'false' }}"
                                @class([
                                    'mt-2 block w-full rounded-md border bg-card px-3 py-2 text-sm text-foreground shadow-sm focus:outline-none focus:ring-2 focus:ring-ring',
                                    'border-input' => ! $errors->has('catatan'),
                                    'border-destructive' => $errors->has('catatan'),
                                ])
                            >{{ old('catatan') }}</textarea>

                            <p id="catatan-help" class="mt-1 text-xs text-muted-foreground">
                                Wajib diisi, maksimal 255 karakter.
                            </p>

                            @error('catatan')
                                <p id="catatan-error" class="mt-2 text-sm text-destructive">{{ $message }}</p>
                            @enderror

                            <button
                                type="submit"
                                class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-md bg-destructive px-4 py-2.5 text-sm font-medium text-destructive-foreground transition-opacity hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-destructive focus:ring-offset-2"
                            >
                                <x-icon name="x" class="h-4 w-4" />
                                Tolak Pendaftaran
                            </button>
                        </form>
                    @else
                        <div class="rounded-md border border-[#FDE047] bg-[#FEF9C3] p-4 text-sm text-[#854D0E]">
                            <div class="flex items-start gap-3">
                                <x-icon name="triangle-alert" class="mt-0.5 h-4 w-4 shrink-0" />
                                <div>
                                    <p class="font-medium">Dokumen legalitas belum tersedia</p>
                                    <p class="mt-1">
                                        Akun ini tidak dapat disetujui atau ditolak sampai dokumen legalitas diunggah.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </section>
        </aside>
    </div>
</x-admin-layout>
