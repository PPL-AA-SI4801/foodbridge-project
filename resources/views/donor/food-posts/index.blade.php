<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="mb-6 rounded-md border border-[#86EFAC] bg-[#DCFCE7] px-4 py-3 text-sm font-medium text-[#166534]">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-md border border-[#FCA5A5] bg-[#FEE2E2] px-4 py-3 text-sm font-medium text-[#B91C1C]">
                {{ session('error') }}
            </div>
        @endif

        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary">Portal Donatur</p>
                <h1 class="mt-1.5 text-2xl font-semibold tracking-tight text-foreground">
                    {{ __('Postingan Makanan') }}
                </h1>
                <p class="mt-1.5 text-sm text-muted-foreground">
                    {{ __('Kelola makanan yang Anda bagikan untuk penerima manfaat.') }}
                </p>
            </div>

            <a href="{{ route('donor.food-posts.create') }}" class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary/30 focus:ring-offset-2">
                {{ __('Buat Postingan') }}
            </a>
        </div>

        <div class="overflow-hidden rounded-2xl border border-border bg-card">
            @if ($foodPosts->isEmpty())
                <div class="p-10 text-center">
                    <h3 class="text-lg font-medium text-foreground">{{ __('Belum ada postingan makanan') }}</h3>
                    <p class="mt-2 text-sm text-muted-foreground">{{ __('Buat postingan pertama Anda agar makanan dapat ditemukan oleh penerima manfaat.') }}</p>
                    <a href="{{ route('donor.food-posts.create') }}" class="mt-5 inline-flex items-center rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90">
                        {{ __('Buat Postingan') }}
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-border text-sm">
                        <thead class="bg-muted">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                <th class="px-6 py-3">{{ __('Makanan') }}</th>
                                <th class="px-6 py-3">{{ __('Kategori') }}</th>
                                <th class="px-6 py-3">{{ __('Porsi') }}</th>
                                <th class="px-6 py-3">{{ __('Batas Konsumsi') }}</th>
                                <th class="px-6 py-3">{{ __('Status') }}</th>
                                <th class="px-6 py-3 text-right">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border bg-card">
                            @foreach ($foodPosts as $foodPost)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <img src="{{ asset('storage/'.$foodPost->foto) }}" alt="{{ $foodPost->judul }}" class="h-14 w-14 rounded-md object-cover" />
                                            <div class="min-w-0">
                                                <p class="font-medium text-foreground">{{ $foodPost->judul }}</p>
                                                <p class="mt-1 max-w-xs truncate text-xs text-muted-foreground">{{ $foodPost->alamat }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ $foodPost->category?->nama_kategori }}</td>
                                    <td class="px-6 py-4 text-muted-foreground">
                                        <span class="font-medium text-foreground">{{ $foodPost->sisa_porsi }}</span>
                                        {{ __('dari') }} {{ $foodPost->jumlah_porsi }}
                                    </td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ $foodPost->batas_konsumsi->format('d/m/Y H:i') }}</td>
                                    <td class="px-6 py-4"><x-status-badge :status="$foodPost->status" /></td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('donor.food-posts.edit', $foodPost) }}" class="font-medium text-primary hover:text-primary/80">
                                                {{ __('Ubah') }}
                                            </a>

                                            <form method="POST" action="{{ route('donor.food-posts.destroy', $foodPost) }}" onsubmit="return confirm('{{ __('Hapus postingan ini?') }}');">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="font-medium text-destructive hover:text-destructive/80">
                                                    {{ __('Hapus') }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($foodPosts->hasPages())
                    <div class="border-t border-border px-6 py-4">
                        {{ $foodPosts->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>