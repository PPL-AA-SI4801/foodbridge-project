<div class="min-h-screen">
    <div class="flex">
        <!-- Sidebar - Penerima style -->
        <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-white border-r">
            <div class="flex items-center h-16 px-6 border-b">
                <div class="flex items-center gap-2">
                    <div class="h-8 w-8 rounded-md bg-sky-600 flex items-center justify-center text-white text-sm font-bold">FB</div>
                    <span class="text-sm font-semibold">FOODBRIDGE</span>
                </div>
            </div>
            <div class="px-6 py-3 text-xs text-gray-500">[Penerima]</div>
            <nav class="flex-1 px-3 py-2 space-y-1">
                <a class="flex items-center gap-2 px-3 py-2 text-sm rounded-md bg-gray-100 text-gray-900" href="#">Katalog Makanan</a>
                <a class="flex items-center gap-2 px-3 py-2 text-sm rounded-md text-gray-700 hover:bg-gray-50" href="#">Klaim Saya</a>
                <a class="flex items-center gap-2 px-3 py-2 text-sm rounded-md text-gray-700 hover:bg-gray-50" href="#">Request Board</a>
                <a class="flex items-center gap-2 px-3 py-2 text-sm rounded-md text-gray-700 hover:bg-gray-50" href="#">Ulasan & Laporan</a>
            </nav>
            <div class="px-3 py-4 border-t">
                <a class="flex items-center gap-2 px-3 py-2 text-sm rounded-md text-gray-700 hover:bg-gray-50" href="#">Logout</a>
            </div>
        </aside>

        <!-- Main content -->
        <div class="lg:pl-64 flex flex-col min-h-screen w-full">
            <!-- Header -->
            <header class="bg-white border-b">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <h1 class="text-base font-semibold text-gray-900">Katalog Makanan</h1>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-amber-50 text-amber-700 text-xs font-medium">Kuota Klaim: 2/3</span>
                        <button class="relative p-2 text-gray-600 hover:text-gray-900">
                            <span class="absolute -top-1 -right-1 h-4 w-4 rounded-full bg-red-500 text-white text-xs flex items-center justify-center">1</span>
                            🔔
                        </button>
                        <button class="flex items-center gap-2 px-2 py-1 text-sm text-gray-700 hover:bg-gray-50 rounded-md">
                            <span class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">P</span>
                            <span>Penerima ▾</span>
                        </button>
                    </div>
                </div>
            </header>

            <main class="flex-1">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <div class="flex items-center gap-2 mb-4">
                        <input class="w-full md:w-80 rounded-md border border-gray-300 px-3 py-2 text-sm" placeholder="Cari makanan..." />
                        <select class="rounded-md border border-gray-300 px-3 py-2 text-sm">
                            <option>Semua Kategori</option>
                        </select>
                        <select class="rounded-md border border-gray-300 px-3 py-2 text-sm">
                            <option>Terdekat</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="rounded-lg border bg-white overflow-hidden">
                            <div class="h-32 bg-gray-100"></div>
                            <div class="p-4">
                                <div class="text-sm font-medium">Nasi Kotak</div>
                                <div class="text-xs text-gray-500 mt-1">Restoran ABC • 1.2 km</div>
                                <div class="mt-3 flex items-center justify-between">
                                    <span class="text-xs text-gray-600">Sisa: 5 porsi</span>
                                    <button class="px-2 py-1 text-xs rounded-md bg-sky-600 text-white">Klaim</button>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-lg border bg-white overflow-hidden">
                            <div class="h-32 bg-gray-100"></div>
                            <div class="p-4">
                                <div class="text-sm font-medium">Roti & Pastry</div>
                                <div class="text-xs text-gray-500 mt-1">Hotel XYZ • 2.1 km</div>
                                <div class="mt-3 flex items-center justify-between">
                                    <span class="text-xs text-gray-600">Sisa: 10 porsi</span>
                                    <button class="px-2 py-1 text-xs rounded-md bg-sky-600 text-white">Klaim</button>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-lg border bg-white overflow-hidden">
                            <div class="h-32 bg-gray-100"></div>
                            <div class="p-4">
                                <div class="text-sm font-medium">Sayur & Lauk</div>
                                <div class="text-xs text-gray-500 mt-1">MBG Kitchen • 0.8 km</div>
                                <div class="mt-3 flex items-center justify-between">
                                    <span class="text-xs text-gray-600">Sisa: 8 porsi</span>
                                    <button class="px-2 py-1 text-xs rounded-md bg-sky-600 text-white">Klaim</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Mobile bottom nav -->
    <nav class="lg:hidden fixed bottom-0 inset-x-0 bg-white border-t">
        <div class="grid grid-cols-4 text-xs">
            <a class="flex flex-col items-center py-2 text-gray-900" href="#"><span>🔍</span><span>Katalog</span></a>
            <a class="flex flex-col items-center py-2 text-gray-600" href="#"><span>📦</span><span>Klaim Saya</span></a>
            <a class="flex flex-col items-center py-2 text-gray-600" href="#"><span>📢</span><span>Request</span></a>
            <a class="flex flex-col items-center py-2 text-gray-600" href="#"><span>⭐</span><span>Ulasan</span></a>
        </div>
    </nav>
</div>
