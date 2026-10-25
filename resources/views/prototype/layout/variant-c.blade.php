<div class="min-h-screen">
    <div class="flex">
        <!-- Sidebar - Admin style -->
        <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-white border-r">
            <div class="flex items-center h-16 px-6 border-b">
                <div class="flex items-center gap-2">
                    <div class="h-8 w-8 rounded-md bg-violet-600 flex items-center justify-center text-white text-sm font-bold">FB</div>
                    <span class="text-sm font-semibold">FOODBRIDGE</span>
                </div>
            </div>
            <div class="px-6 py-3 text-xs text-gray-500">[Administrator]</div>
            <nav class="flex-1 px-3 py-2 space-y-1">
                <a class="flex items-center gap-2 px-3 py-2 text-sm rounded-md bg-gray-100 text-gray-900" href="#">Dashboard</a>
                <a class="flex items-center gap-2 px-3 py-2 text-sm rounded-md text-gray-700 hover:bg-gray-50" href="#">Verifikasi Akun</a>
                <a class="flex items-center gap-2 px-3 py-2 text-sm rounded-md text-gray-700 hover:bg-gray-50" href="#">Laporan Makanan</a>
                <div class="pt-2">
                    <div class="px-3 py-1 text-xs text-gray-400">Manajemen Data</div>
                    <a class="flex items-center gap-2 px-3 py-2 text-sm rounded-md text-gray-700 hover:bg-gray-50" href="#">Pengguna</a>
                    <a class="flex items-center gap-2 px-3 py-2 text-sm rounded-md text-gray-700 hover:bg-gray-50" href="#">Postingan Makanan</a>
                    <a class="flex items-center gap-2 px-3 py-2 text-sm rounded-md text-gray-700 hover:bg-gray-50" href="#">Klaim</a>
                    <a class="flex items-center gap-2 px-3 py-2 text-sm rounded-md text-gray-700 hover:bg-gray-50" href="#">Kategori Gizi</a>
                </div>
                <a class="flex items-center gap-2 px-3 py-2 text-sm rounded-md text-gray-700 hover:bg-gray-50" href="#">Request Board</a>
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
                        <h1 class="text-base font-semibold text-gray-900">Dashboard Administrator</h1>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-red-50 text-red-700 text-xs font-medium">Verifikasi Pending: 5</span>
                        <button class="relative p-2 text-gray-600 hover:text-gray-900">
                            <span class="absolute -top-1 -right-1 h-4 w-4 rounded-full bg-red-500 text-white text-xs flex items-center justify-center">2</span>
                            🔔
                        </button>
                        <button class="flex items-center gap-2 px-2 py-1 text-sm text-gray-700 hover:bg-gray-50 rounded-md">
                            <span class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">A</span>
                            <span>Administrator ▾</span>
                        </button>
                    </div>
                </div>
            </header>

            <main class="flex-1">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-sm text-gray-600">Platform overview</div>
                        <button class="px-3 py-1.5 text-sm rounded-md border bg-white hover:bg-gray-50">Export to Excel</button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="rounded-lg border bg-white p-4">
                            <div class="text-sm text-gray-500">Total Porsi</div>
                            <div class="mt-1 text-2xl font-semibold">2,450</div>
                        </div>
                        <div class="rounded-lg border bg-white p-4">
                            <div class="text-sm text-gray-500">Donor Aktif</div>
                            <div class="mt-1 text-2xl font-semibold">24</div>
                        </div>
                        <div class="rounded-lg border bg-white p-4">
                            <div class="text-sm text-gray-500">Penerima Aktif</div>
                            <div class="mt-1 text-2xl font-semibold">18</div>
                        </div>
                        <div class="rounded-lg border bg-white p-4">
                            <div class="text-sm text-gray-500">Perkiraan Penerima Manfaat</div>
                            <div class="mt-1 text-2xl font-semibold">7,350</div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>
