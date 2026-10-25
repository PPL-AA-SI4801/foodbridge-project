@props(['title' => null, 'subtitle' => null, 'pendingVerifications' => 0])

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
                <header class="flex flex-wrap items-center justify-between gap-4 border-b border-border px-6 py-5 lg:px-8">
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            @click="sidebarOpen = true"
                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-border text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground lg:hidden"
                            aria-label="Buka menu"
                            aria-controls="admin-sidebar"
                        >
                            <x-icon name="menu" class="h-5 w-5" />
                        </button>

                        <div class="flex flex-col gap-1">
                            @if ($title)
                                <h1 class="text-2xl font-semibold">{{ $title }}</h1>
                            @endif

                            @if ($subtitle)
                                <p class="text-sm text-muted-foreground">{{ $subtitle }}</p>
                            @endif
                        </div>
                    </div>

                    @isset($actions)
                        <div class="flex items-center gap-2">
                            {{ $actions }}
                        </div>
                    @endisset
                </header>

                <main class="flex-1 p-6 lg:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
