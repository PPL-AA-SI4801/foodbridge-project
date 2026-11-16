<x-app-layout padded title="Ubah Postingan Makanan" :pending-verifications="$pendingVerifications">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Ubah Postingan Makanan</h1>
        <p class="mt-1 text-sm text-muted-foreground">
            Perbarui detail, ketersediaan porsi, atau status dari postingan makanan "{{ $foodPost->judul }}".
        </p>
    </div>

    @include('admin.food-posts._form', [
        'action' => route('admin.food-posts.update', $foodPost),
        'method' => 'PUT',
        'submitLabel' => 'Simpan Perubahan',
        'foodPost' => $foodPost,
    ])
</x-app-layout>
