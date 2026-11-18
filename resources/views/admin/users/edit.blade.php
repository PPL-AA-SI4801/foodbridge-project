<x-app-layout padded title="Ubah Akun Pengguna" :pending-verifications="$pendingVerifications">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Ubah Akun Pengguna</h1>
        <p class="mt-1 text-sm text-muted-foreground">
            Perbarui data akun dan profil milik {{ $user->nama }}. Peran dan status akun tidak diubah dari halaman ini.
        </p>
    </div>

    @include('admin.users._form', [
        'action' => route('admin.users.update', $user),
        'method' => 'PUT',
        'submitLabel' => 'Simpan Perubahan',
        'user' => $user,
    ])
</x-app-layout>
