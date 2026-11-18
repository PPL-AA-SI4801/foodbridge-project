<x-app-layout padded title="Kategori Gizi" :pending-verifications="$pendingVerifications">
    <x-flash />

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Kategori Gizi</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Kelola kategori gizi yang dapat dipilih pada postingan makanan.
            </p>
        </div>

        <a
            href="{{ route('admin.categories.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/30"
        >
            <x-icon name="plus" class="h-4 w-4" />
            Buat Kategori
        </a>
    </div>

    @if ($categories->isEmpty())
        <div class="rounded-lg border border-border bg-card p-10 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                <x-icon name="tag" class="h-6 w-6" />
            </div>
            <h2 class="mt-4 text-base font-semibold">Belum ada kategori gizi</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Buat kategori pertama agar postingan makanan dapat dikelompokkan.
            </p>
            <a
                href="{{ route('admin.categories.create') }}"
                class="mt-5 inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90"
            >
                <x-icon name="plus" class="h-4 w-4" />
                Buat Kategori
            </a>
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-border bg-card">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-border text-sm">
                    <caption class="sr-only">Daftar kategori gizi</caption>

                    <thead class="bg-muted text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        <tr>
                            <th scope="col" class="px-6 py-3">Nama Kategori</th>
                            <th scope="col" class="px-6 py-3">Jumlah Postingan</th>
                            <th scope="col" class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-border">
                        @foreach ($categories as $category)
                            <tr class="transition-colors hover:bg-muted/50">
                                <td class="px-6 py-4 font-medium text-foreground">
                                    {{ $category->nama_kategori }}
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-muted-foreground">
                                    {{ number_format($category->food_posts_count, 0, ',', '.') }} postingan
                                </td>

                                <td class="whitespace-nowrap px-6 py-4 text-right">
                                    <div class="flex flex-wrap items-center justify-end gap-3">
                                        <a
                                            href="{{ route('admin.categories.edit', $category) }}"
                                            class="font-medium text-primary hover:text-primary/80"
                                        >
                                            Ubah
                                        </a>

                                        @if ($category->food_posts_count === 0)
                                            <form
                                                method="POST"
                                                action="{{ route('admin.categories.destroy', $category) }}"
                                                onsubmit="return confirm('Hapus kategori {{ $category->nama_kategori }}?');"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" dusk="delete-category-{{ $category->id }}" class="font-medium text-destructive hover:text-destructive/80">
                                                    Hapus
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-muted-foreground">Sedang dipakai</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-app-layout>
