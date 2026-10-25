<div class="min-h-screen flex">
    <!-- shadcn-style sidebar: compact rail with labels, icon-first nav, dark inset -->
    <aside class="hidden md:flex w-[72px] flex-col items-center border-r bg-sidebar text-sidebar-foreground py-3">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-sidebar-primary text-sidebar-primary-foreground mb-4">
            <span class="text-xs font-semibold">FB</span>
        </div>
        <nav class="flex flex-1 flex-col items-center gap-1 px-2">
            <a class="group flex h-9 w-9 items-center justify-center rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground" href="#" title="Dashboard">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>
            </a>
            <a class="group flex h-9 w-9 items-center justify-center rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground relative" href="#" title="Verifikasi Akun">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>
                <span class="absolute -top-1 -right-1 h-2 w-2 rounded-full bg-destructive"></span>
            </a>
            <a class="group flex h-9 w-9 items-center justify-center rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground" href="#" title="Laporan Makanan">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2  0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            </a>
            <div class="my-2 h-px w-8 bg-sidebar-border"></div>
            <a class="group flex h-9 w-9 items-center justify-center rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground" href="#" title="Pengguna">
                <svg class class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            </a>
            <a class="group flex h-9 w-9 items-center justify-center rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground" href="#" title="Postingan Makanan">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            </a>
            <a class="group flex h-9 w-9 items-center justify-center rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground" href="#" title="Klaim">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 6"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
            </a>
            <a class="group flex h-9 w-9 items-center justify-center rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground" href="#" title="Kategori Gizi">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
            </a>
            <a class="group flex h-9 w-9 items-center justify-center rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground" href="#" title="Request Board">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
            </a>
        </nav>
        <button class="flex h-9 w-9 items-center justify-center rounded-md hover:bg-sidebar-accent hover:text-sidebar-accent-foreground" title="Logout">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
        </button>
    </aside>
    <main class="flex flex-col flex-1">
        <header class="flex h-14 items-center gap-4 border-b bg-background px-4">
            <div class="flex flex-col">
                <span class class="text-sm font-medium leading-none">Administrator</span>
                <span classver="text-xs text-muted-foreground">FOODBRIDGE</span>
            </div>
            <div class="flex-1"></div>
        </header>
        <div class="flex-1 p-4"></div>
    </main>
</div>
