{{-- Varian B - Rail Ikon: rail sempit ikon-saja, tooltip saat hover, tanpa label. --}}
<div class="flex min-h-screen">
    <aside class="hidden w-20 shrink-0 flex-col items-center border-r border-sidebar-border bg-sidebar py-5 text-sidebar-foreground md:flex">
        {{-- Brand monogram --}}
        <a href="#" class="flex h-11 w-11 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground" aria-label="FoodBridge - Dashboard">
            <x-prototype-admin.icon name="leaf" class="h-5 w-5" />
        </a>

        <div class="mt-5 flex items-center gap-1" aria-label="Masuk sebagai Administrator">
            <span class="inline-flex h-6 items-center rounded-md border border-border bg-muted px-2 text-[11px] font-medium text-muted-foreground">ADMIN</span>
        </div>

        {{-- Navigation --}}
        <nav class="mt-5 flex w-full flex-col items-center gap-1.5">
            <a href="#" aria-label="Dashboard" class="group relative flex h-11 w-11 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                <x-prototype-admin.icon name="layout-dashboard" class="h-5 w-5" />
                <span class="pointer-events-none absolute left-full top-1/2 z-20 ml-2 -translate-y-1/2 whitespace-nowrap rounded-md border border-border bg-popover px-2 py-1 text-xs text-popover-foreground opacity-0 transition-opacity group-hover:opacity-100">Dashboard</span>
            </a>

            <a href="#" aria-label="Verifikasi Akun (5 menunggu)" class="group relative flex h-11 w-11 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                <x-prototype-admin.icon name="user-check" class="h-5 w-5" />
                <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-secondary"></span>
                <span class="pointer-events-none absolute left-full top-1/2 z-20 ml-2 -translate-y-1/2 whitespace-nowrap rounded-md border border-border bg-popover px-2 py-1 text-xs text-popover-foreground opacity-0 transition-opacity group-hover:opacity-100">Verifikasi Akun · 5</span>
            </a>

            <a href="#" aria-label="Laporan Makanan" class="group relative flex h-11 w-11 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                <x-prototype-admin.icon name="triangle-alert" class="h-5 w-5" />
                <span class="pointer-events-none absolute left-full top-1/2 z-20 ml-2 -translate-y-1/2 whitespace-nowrap rounded-md border border-border bg-popover px-2 py-1 text-xs text-popover-foreground opacity-0 transition-opacity group-hover:opacity-100">Laporan Makanan</span>
            </a>

            <div class="my-2 h-px w-8 bg-sidebar-border" aria-hidden="true"></div>

            <a href="#" aria-label="Pengguna" class="group relative flex h-11 w-11 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                <x-prototype-admin.icon name="users" class="h-5 w-5" />
                <span class="pointer-events-none absolute left-full top-1/2 z-20 ml-2 -translate-y-1/2 whitespace-nowrap rounded-md border border-border bg-popover px-2 py-1 text-xs text-popover-foreground opacity-0 transition-opacity group-hover:opacity-100">Pengguna</span>
            </a>

            <a href="#" aria-label="Postingan Makanan" class="group relative flex h-11 w-11 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                <x-prototype-admin.icon name="utensils-crossed" class="h-5 w-5" />
                <span class="pointer-events-none absolute left-full top-1/2 z-20 ml-2 -translate-y-1/2 whitespace-nowrap rounded-md border border-border bg-popover px-2 py-1 text-xs text-popover-foreground opacity-0 transition-opacity group-hover:opacity-100">Postingan Makanan</span>
            </a>

            <a href="#" aria-label="Klaim" class="group relative flex h-11 w-11 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                <x-prototype-admin.icon name="clipboard-check" class="h-5 w-5" />
                <span class="pointer-events-none absolute left-full top-1/2 z-20 ml-2 -translate-y-1/2 whitespace-nowrap rounded-md border border-border bg-popover px-2 py-1 text-xs text-popover-foreground opacity-0 transition-opacity group-hover:opacity-100">Klaim</span>
            </a>

            <a href="#" aria-label="Kategori Gizi" class="group relative flex h-11 w-11 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                <x-prototype-admin.icon name="tags" class="h-5 w-5" />
                <span class="pointer-events-none absolute left-full top-1/2 z-20 ml-2 -translate-y-1/2 whitespace-nowrap rounded-md border border-border bg-popover px-2 py-1 text-xs text-popover-foreground opacity-0 transition-opacity group-hover:opacity-100">Kategori Gizi</span>
            </a>

            <a href="#" aria-label="Request Board" class="group relative flex h-11 w-11 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                <x-prototype-admin.icon name="message-square" class="h-5 w-5" />
                <span class="pointer-events-none absolute left-full top-1/2 z-20 ml-2 -translate-y-1/2 whitespace-nowrap rounded-md border border-border bg-popover px-2 py-1 text-xs text-popover-foreground opacity-0 transition-opacity group-hover:opacity-100">Request Board</span>
            </a>
        </nav>

        {{-- Footer --}}
        <div class="mt-auto flex flex-col items-center gap-1.5">
            <div class="mb-1 h-px w-8 bg-sidebar-border" aria-hidden="true"></div>

            <a href="#" aria-label="Notifikasi" class="group relative flex h-11 w-11 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                <x-prototype-admin.icon name="bell" class="h-5 w-5" />
                <span class="pointer-events-none absolute left-full top-1/2 z-20 ml-2 -translate-y-1/2 whitespace-nowrap rounded-md border border-border bg-popover px-2 py-1 text-xs text-popover-foreground opacity-0 transition-opacity group-hover:opacity-100">Notifikasi</span>
            </a>

            <a href="#" aria-label="Logout" class="group relative flex h-11 w-11 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-accent hover:text-destructive">
                <x-prototype-admin.icon name="log-out" class="h-5 w-5" />
                <span class="pointer-events-none absolute left-full top-1/2 z-20 ml-2 -translate-y-1/2 whitespace-nowrap rounded-md border border-border bg-popover px-2 py-1 text-xs text-popover-foreground opacity-0 transition-opacity group-hover:opacity-100">Logout</span>
            </a>
        </div>
    </aside>

    {{-- Area konten: sengaja kosong --}}
    <div class="hidden flex-1 md:block"></div>
</div>
