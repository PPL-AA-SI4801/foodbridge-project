<x-app-layout padded title="Buat Akun Pengguna" :pending-verifications="$pendingVerifications">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Buat Akun Pengguna</h1>
        <p class="mt-1 text-sm text-muted-foreground">
            Tambahkan akun Donor, Penerima, atau Administrator baru. Akun langsung aktif tanpa dokumen verifikasi.
        </p>
    </div>

    @include('admin.users._form', [
        'action' => route('admin.users.store'),
        'method' => 'POST',
        'submitLabel' => 'Simpan Akun',
        'user' => null,
    ])
</x-app-layout>
