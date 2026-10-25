{{-- Varian A - Klasik: sidebar penuh, grup menu berlabel, footer pengguna. --}}
<div class="flex min-h-screen">
    <aside class="hidden w-72 shrink-0 flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground lg:flex">
        {{-- Header / brand --}}
        <div class="flex flex-col gap-5 p-6">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                    <x-prototype-admin.icon name="leaf" class="h-5 w-5" />
                </div>
                <div class="flex flex-col gap-0.5">
                    <span class="text-sm font-semibold tracking-[0.12em]">FOODBRIDGE</span>
                    <span class="text-xs text-muted-foreground">Panel Administrator</span>
                </div>
            </div>

            <div class="flex items-center gap-2 rounded-md border border-border bg-muted px-3 py-2">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-primary text-xs font-medium text-primary-foreground">A</span>
                <div class="flex min-w-0 flex-col">
                    <span class="truncate text-sm font-medium">Admin Platform</span>
                    <span class="truncate text-xs text-muted-foreground">admin@foodbridge.id</span>
                </div>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto px-3 pb-6">
            <div class="px-3 pb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">Utama</div>
            <div class="flex flex-col gap-1">
                <a href="#" class="flex items-center gap-3 rounded-md bg-sidebar-accent px-3 py-2.5 text-sm font-medium text-sidebar-accent-foreground">
                    <x-prototype-admin.icon name="layout-dashboard" class="h-4 w-4" />
                    <span>Dashboard</span>
                </a>
                <a href="#" class="flex items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                    <x-prototype-admin.icon name="user-check" class="h-4 w-4" />
                    <span>Verifikasi Akun</span>
                    <span class="ml-auto inline-flex h-5 min-w-5 items-center justify-center rounded-md bg-secondary px-1.5 text-xs font-medium text-secondary-foreground">5</span>
                </a>
                <a href="#" class="flex items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                    <x-prototype-admin.icon name="triangle-alert" class="h-4 w-4" />
                    <span>Laporan Makanan</span>
                </a>
            </div>

            <div class="px-3 pb-2 pt-6 text-xs font-medium uppercase tracking-wide text-muted-foreground">Manajemen Data</div>
            <div class="flex flex-col gap-1">
                <a href="#" class="flex items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                    <x-prototype-admin.icon name="users" class="h-4 w-4" />
                    <span>Pengguna</span>
                </a>
                <a href="#" class="flex items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                    <x-prototype-admin.icon name="utensils-crossed" class="h-4 w-4" />
                    <span>Postingan Makanan</span>
                </a>
                <a href="#" class="flex items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                    <x-prototype-admin.icon name="clipboard-check" class="h-4 w-4" />
                    <span>Klaim</span>
                </a>
                <a href="#" class="flex items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                    <x-prototype-admin.icon name="tags" class="h-4 w-4" />
                    <span>Kategori Gizi</span>
                </a>
            </div>

            <div class="px-3 pb-2 pt-6 text-xs font-medium uppercase tracking-wide text-muted-foreground">Lainnya</div>
            <div class="flex flex-col gap-1">
                <a href="#" class="flex items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                    <x-prototype-admin.icon name="message-square" class="h-4 w-4" />
                    <span>Request Board</span>
                </a>
            </div>
        </nav>

        {{-- Footer --}}
        <div class="border-t border-sidebar-border p-3">
            <a href="#" class="flex items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                <x-prototype-admin.icon name="log-out" class="h-4 w-4" />
                <span>Logout</span>
            </a>
        </div>
    </aside>

    {{-- Area konten: sengaja kosong --}}
    <div class="hidden flex-1 lg:block"></div>
</div>
