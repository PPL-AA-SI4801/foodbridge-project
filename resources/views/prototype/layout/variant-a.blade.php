<div class="min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 bg-white border-r">
            <div class="flex items-center h-16 px-6 border-b">
                <div class="flex items-center gap-2">
                    <div class="h-8 w-8 rounded-md bg-emerald-600 flex items-center justify-center text-white text-sm font-bold">FB</div>
                    <span class="text-sm font-semibold">FOODBRIDGE</span>
                </div>
            </div>
            <div class="px-6 py-3 text-xs text-gray-500">[Donor]</div>
            <nav class="flex-1 px-3 py-2 space-y-1">
                <a class="flex items-center gap-2 px-3 py-2 text-sm rounded-md bg-gray-100 text-gray-900" href="#">Dashboard</a>
                <a class="flex items-center gap-2 px-3 py-2 text-sm rounded-md text-gray-700 hover:bg-gray-50" href="#">Posting Makanan</a>
                <a class="flex items-center gap-2 px-3 py-2 text-sm rounded-md text-gray-700 hover:bg-gray-50" href="#">Klaim Masuk</a>
                <a class="flex items-center gap-2 px-3 py-2 text-sm rounded-md text-gray-700 hover:bg-gray-50" href="#">Request Board</a>
                <a class="flex items-center gap-2 px-3 py-2 text-sm rounded-md text-gray-700 hover:bg-gray-50" href="#">Laporan</a>
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
                        <h1 class="text-base font-semibold text-gray-900">Dashboard</h1>
                    </div>
                    <div class="flex items-center gap-3">
                        <button class="inline-flex items-center px-3 py-1.5 text-sm rounded-md bg-emerald-600 text-white hover:bg-emerald-700">+ Posting Makanan</button>
                        <button class="relative p-2 text-gray-600 hover:text-gray-900">
                            <span class="absolute -top-1 -right-1 h-4 w-4 rounded-full bg-red-500 text-white text-xs flex items-center justify-center">3</span>
                            🔔
                        </button>
                        <button class="flex items-center gap-2 px-2 py-1 text-sm text-gray-700 hover:bg-gray-50 rounded-md">
                            <span class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">D</span>
                            <span>Donor ▾</span>
                        </button>
                    </div>
                </div>
            </header>

            <main class="flex-1">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="rounded-lg border bg-white p-4">
                            <div class="text-sm text-gray-500">Porsi Terdistribusi</div>
                            <div class="mt-1 text-2xl font-semibold">128</div>
                        </div>
                        <div class="rounded-lg border bg-white p-4">
                            <div class="text-sm text-gray-500">Penerima</div>
                            <div class="mt-1 text-2xl font-semibold">12</div>
                        </div>
                        <div class="rounded-lg border bg-white p-4">
                            <div class="text-sm text-gray-500">Perkiraan Penerima Manfaat</div>
                            <div class="mt-1 text-2xl font-semibold">384</div>
                        </div>
                    </div>
                    <div class="mt-6 rounded-lg border bg-white p-6">
                        <div class="text-sm text-gray-500 mb-4">Impact Chart</div>
                        <div class="h-64 w-full bg-gradient-to-br from-gray-50 to-gray-100 rounded-md flex items-center justify-center text-gray-400 text-sm">[Chart Placeholder]</div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Mobile bottom nav for Penerima-like quick access concept not shown; variant A is Donor -->
    <nav class="lg:hidden fixed bottom-0 inset-x-0 bg-white border-t">
        <div class="grid grid-cols-5 text-xs">
            <a class="flex flex-col items-center py-2 text-gray-900" href="#"><span>📊</span><span>Dashboard</span></a>
            <a class="flex flex-col items-center py-2 text-gray-600" href="#"><span>🍱</span><span>Posting</span></a>
            <a class="flex flex-col items-center py-2 text-gray-600" href="#"><span>📥</span><span>Klaim</span></a>
            <a class="flex flex-col items-center py-2 text-gray-600" href="#"><span>📢</span><span>Request</span></a>
            <a class="flex flex-col items-center py-2 text-gray-600" href="#"><span>📄</span><span>Laporan</span></a>
        </div>
    </nav>
</div>
