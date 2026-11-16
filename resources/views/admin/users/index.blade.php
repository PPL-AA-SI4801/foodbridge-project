<x-app-layout padded title="Manajemen Pengguna" :pending-verifications="$pendingVerifications">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Manajemen Pengguna</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Kelola akun Donor dan Penerima yang terdaftar di FoodBridge.
            </p>
        </div>

        <div class="flex flex-wrap gap-2 text-sm">
            <span class="inline-flex items-center gap-2 rounded-full border border-border bg-card px-3 py-1.5 text-muted-foreground">
                <span class="font-semibold text-foreground">{{ $totalUsers }}</span>
                total akun
            </span>
            <span class="inline-flex items-center gap-2 rounded-full border border-border bg-card px-3 py-1.5 text-muted-foreground">
                <span class="font-semibold text-foreground">{{ $blockedUsers }}</span>
                diblokir
            </span>
        </div>
    </div>

    <x-flash />

    <div class="overflow-hidden rounded-lg border border-border bg-card">
        <div class="border-b border-border p-4">
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="q" class="block text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        Cari pengguna
                    </label>
                    <input
                        id="q"
                        name="q"
                        type="search"
                        value="{{ $search }}"
                        placeholder="Nama, email, atau nomor HP"
                        class="mt-1.5 block w-full rounded-md border border-input bg-white px-3 py-2 text-sm text-foreground shadow-xs placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    >
                </div>

                <div class="sm:w-48">
                    <label for="status" class="block text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        Status
                    </label>
                    <select
                        id="status"
                        name="status"
                        class="mt-1.5 block w-full rounded-md border border-input bg-white px-3 py-2 text-sm text-foreground shadow-xs focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    >
                        <option value="">Semua status</option>
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption->value }}" @selected($status === $statusOption->value)>
                                {{ ucfirst($statusOption->value) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-opacity hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                    >
                        <x-icon name="search" class="h-4 w-4" />
                        Cari
                    </button>

                    @if ($search !== '' || $status !== null)
                        <a
                            href="{{ route('admin.users.index') }}"
                            class="inline-flex items-center justify-center rounded-md border border-input bg-card px-4 py-2 text-sm font-medium transition-colors hover:bg-accent focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                        >
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        @if ($users->isEmpty())
            <div class="p-10 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                    <x-icon name="users" class="h-6 w-6" />
                </div>
                <h2 class="mt-4 text-base font-semibold">Tidak ada pengguna yang cocok</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Coba ubah kata kunci pencarian atau filter status.
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border text-sm">
                    <caption class="sr-only">Daftar akun Donor dan Penerima</caption>

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
                                        {{ ucfirst($user->role->value) }}
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
                                    @if ($user->status_akun === \App\Enums\AccountStatus::Menunggu)
                                        <a
                                            href="{{ route('admin.verifications.show', $user) }}"
                                            class="inline-flex items-center gap-1 rounded-md border border-input bg-card px-3 py-2 text-sm font-medium transition-colors hover:bg-accent focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                        >
                                            Tinjau
                                            <x-icon name="chevron-right" class="h-4 w-4" />
                                        </a>
                                    @elseif ($user->status_akun === \App\Enums\AccountStatus::Aktif)
                                        <form
                                            method="POST"
                                            action="{{ route('admin.users.block', $user) }}"
                                            class="inline"
                                            onsubmit="return confirm('Blokir akun {{ $user->nama }}? Akun ini tidak akan bisa masuk lagi.');"
                                        >
                                            @csrf

                                            <button
                                                type="submit"
                                                dusk="block-user-{{ $user->id }}"
                                                class="inline-flex items-center gap-1 rounded-md bg-destructive px-3 py-2 text-sm font-medium text-destructive-foreground transition-opacity hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-destructive focus:ring-offset-2"
                                            >
                                                <x-icon name="lock" class="h-4 w-4" />
                                                Blokir
                                            </button>
                                        </form>
                                    @elseif ($user->status_akun === \App\Enums\AccountStatus::Diblokir)
                                        <span class="text-xs font-medium text-muted-foreground">Sudah diblokir</span>
                                    @else
                                        <span class="text-muted-foreground">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-border px-6 py-4">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
