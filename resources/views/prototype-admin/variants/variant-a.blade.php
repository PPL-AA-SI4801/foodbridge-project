<div class="grid min-h-screen w-full md:grid-cols-[280px_1fr]">
    <!-- shadcn-style sidebar: collapsible rail feel, grouped nav, subtle -->
    <aside class="hidden md:flex flex-col border-r bg-sidebar text-sidebar-foreground">
        <div class="flex h-14 items-center px-4 border-b border-sidebar-border">
            <div class="flex items-center gap-2">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                    <span class="text-xs font-semibold">FB</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-semibold leading-none">FOODBRIDGE</span>
                    <span class="text-xs text-sidebar-foreground/70">Administrator</span>
                </div>
            </div>
        </div>
        <nav class="flex-1 overflow-auto px-2 py-3">
            <div class="px-2 py-1.5 text-xs font-medium text-sidebar-foreground/70">Utama</div>
            <div class="space-y-1">
                <a class="group flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-sidebar-accent hover:text-sidebar-accent-foreground" href="#">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
                    <span>Dashboard</span>
                </a>
                <a class="group flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-sidebar-accent hover:text-sidebar-accent-foreground" href="#">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>
                    <span>Verifikasi Akun</span>
                    <span class="ml-auto inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-sidebar-primary px-1 text-[10px] font-medium text-sidebar-primary-foreground">5</span>
                </a>
                <a class="group flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-sidebar-accent hover:text-sidebar-accent-foreground" href="#">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    <span>Laporan Makanan</span>
                </a>
            </div>
            <div class="mt-4 px-2 py-1.5 text-xs font-medium text-sidebar-foreground/70">Manajemen Data</div>
            <div class="space-y-1">
                <a class="group flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-sidebar-accent hover:text-sidebar-accent-foreground" href="#">Pengguna</a>
                <a class="group flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-sidebar-accent hover:text-sidebar-accent-foreground" href="#">Postingan Makanan</a>
                <a class="group flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-sidebar-accent hover:text-sidebar-accent-foreground" href="#">Klaim</a>
                <a class="group flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-sidebar-accent hover:text-sidebar-accent-foreground" href="#">Kategori Gizi</a>
            </div>
            <div class="mt-4 px-2 py-1.5 text-xs font-medium text-sidebar-foreground/70">Lainnya</div>
            <div class="space-y-1">
                <a class="group flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-sidebar-accent hover:text-sidebar-accent-foreground" href="#">Request Board</a>
            </div>
        </nav>
        <div class="mt-auto border-t border-sidebar-border p-2">
            <button class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                <span>Logout</span>
            </button>
        </div>
    </aside>
    <main class="flex flex-col">
        <header class="flex h-14 items-center gap-4 border-b bg-background px-4">
            <div class="flex-1"></div>
        </header>
        <div class="flex-1 p-4"></div>
    </main>
</div>
