{{-- Varian C - Menempel: sidebar penuh, pencarian, submenu bertingkat. --}}
<div class="flex min-h-screen bg-background">
    <aside class="hidden w-[19rem] shrink-0 flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground lg:flex">
        {{-- Header / brand --}}
        <div class="flex items-center gap-3 border-b border-sidebar-border p-5">
            <div class="flex h-10 w-10 items-center justify-center rounded-md bg-primary text-primary-foreground">
                <x-prototype-admin.icon name="leaf" class="h-5 w-5" />
            </div>
            <div class="flex flex-col gap-0.5">
                <span class="text-sm font-semibold tracking-[0.12em]">FOODBRIDGE</span>
                <span class="text-xs text-muted-foreground">Panel Administrator</span>
            </div>
        </div>

        {{-- Pencarian --}}
        <div class="px-4 pt-4">
            <div class="relative">
                <x-prototype-admin.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <input
                    type="search"
                    placeholder="Cari menu…"
                    class="h-9 w-full rounded-md border border-input bg-transparent pl-9 pr-3 text-sm text-foreground placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-[3px] focus:ring-ring/20"
                />
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto px-3 py-4">
            <div class="flex flex-col gap-1">
                <a href="#" class="relative flex items-center gap-3 rounded-md bg-accent px-3 py-2.5 text-sm font-medium text-accent-foreground">
                    <span class="absolute bottom-2 left-0 top-2 w-0.5 rounded-full bg-secondary" aria-hidden="true"></span>
                    <x-prototype-admin.icon name="layout-dashboard" class="h-4 w-4" />
                    <span>Dashboard</span>
                </a>

                <a href="#" class="relative flex items-center gap-3 rounded-md px-3 py-2.5 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">
                    <x-prototype-admin.icon name="user-check" class="h-4 w-4" />
                    <span>Verifikasi Akun</span>
                    <span class="ml-auto inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-secondary px-1.5 text-xs font-medium text-white">5</span>
                </a>

                <a href="#" class="relative flex items-center gap-3 rounded-md px-3 py-2.5 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">
                    <x-prototype-admin.icon name="triangle-alert" class="h-4 w-4" />
                    <span>Laporan Makanan</span>
                </a>

                <a href="#" class="relative flex items-center gap-3 rounded-md px-3 py-2.5 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">
                    <x-prototype-admin.icon name="message-square" class="h-4 w-4" />
                    <span>Request Board</span>
                </a>
            </div>

            {{-- Grup bertingkat --}}
            <div class="mt-5 border-t border-border pt-5">
                <div class="flex items-center gap-3 px-3 pb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                    <x-prototype-admin.icon name="database" class="h-3.5 w-3.5" />
                    <span>Manajemen Data</span>
                    <x-prototype-admin.icon name="chevron-down" class="ml-auto h-3.5 w-3.5" />
                </div>

                <div class="ml-[1.4rem] flex flex-col gap-0.5 border-l border-border pl-4">
                    <a href="#" class="rounded-md px-3 py-2 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">Pengguna</a>
                    <a href="#" class="rounded-md px-3 py-2 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">Postingan Makanan</a>
                    <a href="#" class="rounded-md px-3 py-2 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">Klaim</a>
                    <a href="#" class="rounded-md px-3 py-2 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">Kategori Gizi</a>
                </div>
            </div>
        </nav>

        {{-- Footer --}}
        <div class="border-t border-sidebar-border p-4">
            <div class="mb-3 flex items-center gap-3 px-1">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-primary text-xs font-medium text-primary-foreground">A</span>
                <div class="flex min-w-0 flex-col">
                    <span class="truncate text-sm font-medium">Admin Platform</span>
                    <span class="truncate text-xs text-muted-foreground">admin@foodbridge.id</span>
                </div>
            </div>

            <a href="#" class="flex w-full items-center justify-center gap-2 rounded-md border border-input px-3 py-2 text-sm text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">
                <x-prototype-admin.icon name="log-out" class="h-4 w-4" />
                <span>Logout</span>
            </a>
        </div>
    </aside>

    {{-- Area konten: sengaja kosong --}}
    <div class="hidden flex-1 lg:block"></div>
</div>
