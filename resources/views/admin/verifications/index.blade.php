<x-admin-layout title="Verifikasi Akun" :pending-verifications="$pendingVerifications">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Verifikasi Akun</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Tinjau pendaftaran Donor dan Penerima yang menunggu persetujuan.
            </p>
        </div>

        <span class="inline-flex w-fit items-center gap-2 rounded-full border border-border bg-card px-3 py-1.5 text-sm text-muted-foreground">
            <span class="font-semibold text-foreground">{{ $pendingVerifications }}</span>
            pendaftaran menunggu
        </span>
    </div>

    @if ($applicants->isEmpty())
        <div class="rounded-lg border border-border bg-card p-10 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                <x-icon name="user-check" class="h-6 w-6" />
            </div>
            <h2 class="mt-4 text-base font-semibold">Tidak ada pendaftaran menunggu verifikasi</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Semua pendaftaran Donor dan Penerima sudah diproses.
            </p>
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-border bg-card">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border text-sm">
                    <caption class="sr-only">Daftar pendaftaran yang menunggu verifikasi</caption>

                    <thead class="bg-muted text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th scope="col" class="px-6 py-3">Pendaftar</th>
                            <th scope="col" class="px-6 py-3">Peran</th>
                            <th scope="col" class="px-6 py-3">Usaha / Lembaga</th>
                            <th scope="col" class="px-6 py-3">Dokumen</th>
                            <th scope="col" class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-border">
                        @foreach ($applicants as $applicant)
                            @php
                                $document = $applicant->verificationDocuments->first();
                                $profileDetail = match ($applicant->role) {
                                    \App\Enums\Role::Donor => $applicant->donorProfile?->jenis_usaha,
                                    \App\Enums\Role::Penerima => $applicant->recipientProfile?->jenis_lembaga,
                                    default => null,
                                };
                            @endphp

                            <tr class="transition-colors hover:bg-muted/50">
                                <td class="px-6 py-4">
                                    <div class="font-medium">{{ $applicant->nama }}</div>
                                    <div class="mt-1 text-xs text-muted-foreground">{{ $applicant->email }}</div>
                                    <div class="text-xs text-muted-foreground">{{ $applicant->no_hp }}</div>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="inline-flex items-center rounded-full border border-border bg-muted px-2.5 py-0.5 text-xs font-medium">
                                        {{ ucfirst($applicant->role->value) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="font-medium">{{ $applicant->profileName() ?? '—' }}</div>
                                    @if ($profileDetail)
                                        <div class="mt-1 text-xs text-muted-foreground">{{ $profileDetail }}</div>
                                    @endif
                                </td>

                                <td class="px-6 py-4">
                                    @if ($document)
                                        <div class="font-medium">{{ $document->jenisDokumenLabel() }}</div>
                                        <div class="mt-1">
                                            <x-status-badge :status="$document->status" />
                                        </div>
                                    @else
                                        <span class="text-muted-foreground">Belum ada dokumen</span>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <a
                                        href="{{ route('admin.verifications.show', $applicant) }}"
                                        class="inline-flex items-center gap-2 rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground transition-opacity hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                    >
                                        Tinjau
                                        <x-icon name="chevron-right" class="h-4 w-4" />
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-admin-layout>
