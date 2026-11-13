@php
    $inputClasses = 'block w-full rounded-lg border border-input bg-white px-3 py-2 text-sm text-foreground shadow-xs transition placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20';
    $roleLabels = [
        'donor' => 'Donor',
        'penerima' => 'Penerima',
        'administrator' => 'Administrator',
    ];
@endphp

<x-app-layout padded title="Manajemen Pengguna" :pending-verifications="$pendingVerifications">
    <x-flash />

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Manajemen Pengguna</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Kelola akun Donor, Penerima, dan Administrator yang terdaftar di FoodBridge.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-2 rounded-full border border-border bg-card px-3 py-1.5 text-sm text-muted-foreground">
                <span class="font-semibold text-foreground">{{ $totalUsers }}</span>
                total akun
            </span>
            <span class="inline-flex items-center gap-2 rounded-full border border-border bg-card px-3 py-1.5 text-sm text-muted-foreground">
                <span class="font-semibold text-foreground">{{ $blockedUsers }}</span>
                diblokir
            </span>
            <a
                href="{{ route('admin.users.create') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/30"
            >
                <x-icon name="plus" class="h-4 w-4" />
                Buat Akun
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" class="mb-6 rounded-xl border border-border bg-card p-4">
        <div class="grid gap-3 sm:grid-cols-4">
            <div class="sm:col-span-2">
                <label for="q" class="sr-only">Cari pengguna</label>
                <input
                    id="q"
                    name="q"
                    type="search"
                    value="{{ $search }}"
                    placeholder="Cari nama, email, no HP, usaha, atau lembaga..."
                    class="{{ $inputClasses }}"
                />
            </div>

            <div>
                <label for="role" class="sr-only">Peran</label>
                <select id="role" name="role" class="{{ $inputClasses }}">
                    <option value="">Semua peran</option>
                    @foreach ($roles as $roleOption)
                        <option value="{{ $roleOption->value }}" @selected($activeRole === $roleOption->value)>
                            {{ $roleLabels[$roleOption->value] ?? ucfirst($roleOption->value) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <div class="flex-1">
                    <label for="status" class="sr-only">Status akun</label>
                    <select id="status" name="status" class="{{ $inputClasses }}">
                        <option value="">Semua status</option>
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption->value }}" @selected($activeStatus === $statusOption->value)>
                                {{ ucfirst($statusOption->value) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button
                    type="submit"
                    class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-lg bg-primary px-3.5 py-2 text-xs font-semibold text-primary-foreground transition hover:bg-primary/90"
                >
                    <x-icon name="search" class="h-4 w-4" />
                    Filter
                </button>
            </div>
        </div>

        @if ($search !== '' || $activeRole !== null || $activeStatus !== null)
            <div class="mt-3">
                <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-primary hover:text-primary/80">
                    Reset filter
                </a>
            </div>
        @endif
    </form>

    @if ($users->isEmpty())
        <div class="rounded-lg border border-border bg-card p-10 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                <x-icon name="users" class="h-6 w-6" />
            </div>
            <h2 class="mt-4 text-base font-semibold">Tidak ada pengguna yang cocok</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Coba ubah kata kunci pencarian atau filter peran dan status.
            </p>
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-border bg-card">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border text-sm">
                    <caption class="sr-only">Daftar akun Donor, Penerima, dan Administrator</caption>

                    <thead class="bg-muted text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th scope="col" class="px-6 py-3">Pengguna</th>
                            <th scope="col" class="px-6 py-3">Peran</th>
                            <th scope="col" class="px-6 py-3">Usaha / Lembaga</th>
                            <th scope="col" class="px-6 py-3">Status</th>
                            <th scope="col" class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-border">
                        @foreach ($users as $user)
                            @php
                                $profileType = match ($user->role) {
                                    \App\Enums\Role::Donor => $user->donorProfile?->jenis_usaha,
                                    \App\Enums\Role::Penerima => $user->recipientProfile?->jenis_lembaga,
                                    default => null,
                                };
                            @endphp

                            <tr class="transition-colors hover:bg-muted/50">
                                <td class="px-6 py-4">
                                    <div class="font-medium">{{ $user->nama }}</div>
                                    <div class="mt-1 text-xs text-muted-foreground">{{ $user->email }}</div>
                                    <div class="text-xs text-muted-foreground">{{ $user->no_hp }}</div>
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="inline-flex items-center rounded-full border border-border bg-muted px-2.5 py-0.5 text-xs font-medium">
                                        {{ $roleLabels[$user->role->value] ?? ucfirst($user->role->value) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="font-medium">{{ $user->profileName() ?? '—' }}</div>
                                    @if ($profileType)
                                        <div class="mt-1 text-xs text-muted-foreground">{{ $profileType }}</div>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-6 py-4">
                                    <x-status-badge :status="$user->status_akun" />
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <div class="flex flex-wrap items-center justify-end gap-3">
                                        @if ($user->hasRole(\App\Enums\Role::Donor, \App\Enums\Role::Penerima) && $user->status_akun === \App\Enums\AccountStatus::Menunggu)
                                            <a
                                                href="{{ route('admin.verifications.show', $user) }}"
                                                class="font-medium text-primary hover:text-primary/80"
                                            >
                                                Tinjau
                                            </a>
                                        @endif

                                        @if ($user->hasRole(\App\Enums\Role::Donor, \App\Enums\Role::Penerima) && $user->status_akun === \App\Enums\AccountStatus::Aktif)
                                            <form
                                                method="POST"
                                                action="{{ route('admin.users.block', $user) }}"
                                                onsubmit="return confirm('Blokir akun {{ $user->nama }}? Akun ini tidak akan bisa masuk lagi.');"
                                            >
                                                @csrf
                                                <button type="submit" dusk="block-user-{{ $user->id }}" class="font-medium text-destructive hover:text-destructive/80">
                                                    Blokir
                                                </button>
                                            </form>
                                        @endif

                                        @if ($user->status_akun === \App\Enums\AccountStatus::Diblokir)
                                            <span class="text-xs font-medium text-muted-foreground">Sudah diblokir</span>
                                        @endif

                                        <a
                                            href="{{ route('admin.users.edit', $user) }}"
                                            class="font-medium text-primary hover:text-primary/80"
                                        >
                                            Ubah
                                        </a>

                                        <form
                                            method="POST"
                                            action="{{ route('admin.users.destroy', $user) }}"
                                            onsubmit="return confirm('Hapus akun {{ $user->nama }}? Tindakan ini tidak dapat dibatalkan.');"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" dusk="delete-user-{{ $user->id }}" class="font-medium text-destructive hover:text-destructive/80">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="border-t border-border px-6 py-4">
                    {{ $users->onEachSide(1)->links() }}
                </div>
            @endif
        </div>
    @endif
</x-app-layout>
