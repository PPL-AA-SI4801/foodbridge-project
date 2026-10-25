@props(['title' => null, 'pendingVerifications' => 0])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name', 'FoodBridge') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-background font-sans text-foreground antialiased">
        <div
            class="flex min-h-screen"
            x-data="{ sidebarOpen: false }"
            @close-sidebar.window="sidebarOpen = false"
            @keydown.escape.window="sidebarOpen = false"
        >
            {{-- Latar gelap untuk sidebar versi mobile --}}
            <div
                x-show="sidebarOpen"
                x-cloak
                @click="sidebarOpen = false"
                class="fixed inset-0 z-40 bg-foreground/30 lg:hidden"
                aria-hidden="true"
            ></div>

            <x-admin.sidebar :pending-verifications="$pendingVerifications" />

            <div class="flex min-w-0 flex-1 flex-col">
                <header class="border-b border-border bg-white">
                    <div class="flex h-16 items-center justify-between gap-6 px-6 lg:px-8">
                        <div class="flex min-w-0 items-center gap-3">
                            <button
                                type="button"
                                @click="sidebarOpen = true"
                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-border text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground lg:hidden"
                                aria-label="Buka menu"
                                aria-controls="admin-sidebar"
                            >
                                <x-icon name="menu" class="h-5 w-5" />
                            </button>

                            <nav aria-label="Breadcrumb" class="flex min-w-0 items-center gap-2 text-sm">
                                <a href="{{ route('admin.dashboard') }}" class="shrink-0 text-muted-foreground transition-colors hover:text-foreground">Beranda</a>

                                @if ($title)
                                    <x-icon name="chevron-right" class="h-4 w-4 shrink-0 text-muted-foreground" />
                                    <span class="truncate font-medium">{{ $title }}</span>
                                @endif
                            </nav>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            @isset($actions)
                                {{ $actions }}
                            @endisset

                            <span class="inline-flex h-9 items-center gap-2 rounded-md border border-border bg-card px-3 text-sm">
                                <x-icon name="user-check" class="h-4 w-4 text-muted-foreground" />
                                <span class="hidden text-muted-foreground sm:inline">Verifikasi Pending:</span>
                                <span class="font-semibold">{{ $pendingVerifications }}</span>
                            </span>

                            <x-header.notifications />

                            <div class="h-6 w-px bg-border" aria-hidden="true"></div>

                            <x-header.profile-menu />
                        </div>
                    </div>
                </header>

                <main class="flex-1 p-6 lg:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
