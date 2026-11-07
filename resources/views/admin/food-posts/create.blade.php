<x-admin-layout title="Buat Postingan Makanan" :pending-verifications="$pendingVerifications">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Buat Postingan Makanan</h1>
        <p class="mt-1 text-sm text-muted-foreground">
            Tambahkan postingan surplus makanan baru atas nama donatur terdaftar.
        </p>
    </div>

    @include('admin.food-posts._form', [
        'action' => route('admin.food-posts.store'),
        'method' => 'POST',
        'submitLabel' => 'Simpan Postingan',
        'foodPost' => null,
    ])
</x-admin-layout>
