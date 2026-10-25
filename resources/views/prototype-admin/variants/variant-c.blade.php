<div class="grid min-h-screen w-full lg:grid-cols-[320px_1fr]">
    <!-- shadcn-style sidebar: inset panel with header card, accordion-style groups -->
    <aside class="hidden lg:flex flex-col gap-4 p-4 border-r bg-background">
        <div class="flex items-center gap-3 rounded-lg border bg-card p-3 shadow-sm">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                <span class="text-xs font-semibold">FB</span>
            </div>
            <div class="flex flex-col">
                <span class="text-sm font-medium leading-none">FOODBRIDGE</span>
                <span class="text-xs text-muted-foreground">Panel Administrator</span>
            </div>
        </div>
        <nav class="flex-1 flex flex-col gap-2">
            <div>
                <div class="mb-1 px-2 text-xs font-medium text-muted-foreground">Menu</div>
                <div class="space-y-1">
                    <a class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground" href="#">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
                        <span>Dashboard</span>
                    </a>
                    <a class="flex items-center justify-between rounded-md px-2 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground" href="#">
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>
                            <span>Verifikasi Akun</span>
                        </div>
                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-destructive px-1 text-[10px] font-medium text-destructive-foreground">5</span>
                    </a>
                    <a class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground" href="#">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0  0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2"13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                        <span>Laporan Makanan</span>
                    </a>
                    <a class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground" href="#">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                        <span>Request Board</span>
                    </a>
                </div>
            </div>
            <div>
                <div class="mb-1 px-2 text-xs font-medium text-muted-foreground">Manajemen Data</div>
                <div class="rounded-md border bg-card">
                    <div class="space-y-1 p-1">
                        <a class="flex items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground" href="#">Pengguna</a>
                        <a class="flex items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground" href="#">Postingan Makanan</a>
                        <a class="flex items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent hover:text-actag">
                        <a class="flex items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground" href="#">Klaim</a>
                        <a class="flex items-center gap-2 rounded-sm px-2 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground" href="#">Kategori Gizi</a>
                    </div>
                </div>
            </div>
        </nav>
        <button class="flex items-center gap-2 rounded-md border bg-card px-2 py-1.5 text-sm shadow-sm hover:bg-accent hover:text-accent-foreground">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            <span>Logout</span>
        </button>
    </aside>
    <main class="flex flex-col">
        <header class="flex h-14 items-center gap-4 border-b bg-background px-4">
            <div class="flex-1"></div>
        </header>
        <div class="flex-1 p-4"></div>
    </main>
</div>
