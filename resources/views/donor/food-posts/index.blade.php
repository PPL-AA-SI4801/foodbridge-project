<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Postingan Makanan') }}
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    {{ __('Kelola makanan yang Anda bagikan untuk penerima manfaat.') }}
                </p>
            </div>

            <a href="{{ route('donor.food-posts.create') }}" class="inline-flex items-center justify-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                {{ __('Buat Postingan') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                @if ($foodPosts->isEmpty())
                    <div class="p-10 text-center">
                        <h3 class="text-lg font-medium text-gray-900">{{ __('Belum ada postingan makanan') }}</h3>
                        <p class="mt-2 text-sm text-gray-600">{{ __('Buat postingan pertama Anda agar makanan dapat ditemukan oleh penerima manfaat.') }}</p>
                        <a href="{{ route('donor.food-posts.create') }}" class="mt-5 inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-gray-700">
                            {{ __('Buat Postingan') }}
                        </a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    <th class="px-6 py-3">{{ __('Makanan') }}</th>
                                    <th class="px-6 py-3">{{ __('Kategori') }}</th>
                                    <th class="px-6 py-3">{{ __('Porsi') }}</th>
                                    <th class="px-6 py-3">{{ __('Batas Konsumsi') }}</th>
                                    <th class="px-6 py-3">{{ __('Status') }}</th>
                                    <th class="px-6 py-3 text-right">{{ __('Aksi') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach ($foodPosts as $foodPost)
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <img src="{{ asset('storage/'.$foodPost->foto) }}" alt="{{ $foodPost->judul }}" class="h-14 w-14 rounded-md object-cover" />
                                                <div class="min-w-0">
                                                    <p class="font-medium text-gray-900">{{ $foodPost->judul }}</p>
                                                    <p class="mt-1 max-w-xs truncate text-xs text-gray-500">{{ $foodPost->alamat }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-gray-700">{{ $foodPost->category?->nama_kategori }}</td>
                                        <td class="px-6 py-4 text-gray-700">
                                            <span class="font-medium text-gray-900">{{ $foodPost->sisa_porsi }}</span>
                                            {{ __('dari') }} {{ $foodPost->jumlah_porsi }}
                                        </td>
                                        <td class="px-6 py-4 text-gray-700">{{ $foodPost->batas_konsumsi->format('d/m/Y H:i') }}</td>
                                        <td class="px-6 py-4"><x-status-badge :status="$foodPost->status" /></td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-end gap-3">
                                                <a href="{{ route('donor.food-posts.edit', $foodPost) }}" class="font-medium text-indigo-600 hover:text-indigo-800">
                                                    {{ __('Ubah') }}
                                                </a>

                                                <form method="POST" action="{{ route('donor.food-posts.destroy', $foodPost) }}" onsubmit="return confirm('{{ __('Hapus postingan ini?') }}');">
                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="submit" class="font-medium text-red-600 hover:text-red-800">
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
                        <div class="border-t border-gray-200 px-6 py-4">
                            {{ $foodPosts->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
