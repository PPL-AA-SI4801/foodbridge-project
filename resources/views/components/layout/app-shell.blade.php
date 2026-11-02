@props([
    'title' => null,
    'displayName' => null,
    'maxWidth' => 'max-w-7xl',
    'stickyHeader' => false,
    'sidebar' => null,
])

@php
    $user = auth()->user();
    $dashboardRoute = $user->dashboardRouteName();
    $displayName = $displayName ?? $user->profileName();
    $roleLabel = $user->hasRole(\App\Enums\Role::Donor)
        ? 'Donor Hub'
        : ($user->hasRole(\App\Enums\Role::Penerima) ? 'Penerima / Yayasan' : null);
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?: config('app.name', 'FoodBridge') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-background font-sans text-foreground antialiased">
        <div
            class="flex min-h-screen"
            x-data="{ sidebarOpen: false }"
            @close-sidebar.window="sidebarOpen = false"
            @keydown.escape.window="sidebarOpen = false"
            @resize.window="if (window.innerWidth >= 1024) sidebarOpen = false"
        >
            {{-- Latar gelap untuk sidebar versi mobile --}}
            <div
                x-show="sidebarOpen"
                x-cloak
                @click="sidebarOpen = false"
                class="fixed inset-0 z-40 bg-foreground/30 lg:hidden"
                aria-hidden="true"
            ></div>

            @if (isset($sidebar))
                {{ $sidebar }}
            @elseif ($user->hasRole(\App\Enums\Role::Donor))
                <x-donor.sidebar />
            @elseif ($user->hasRole(\App\Enums\Role::Penerima))
                <x-recipient.sidebar />
            @else
                <x-admin.sidebar />
            @endif

            <div class="flex min-w-0 flex-1 flex-col">
                <x-layout.header
                    :max-width="$maxWidth"
                    :sticky="$stickyHeader"
                    :hide-logo-on-desktop="true"
                    :role-label="$roleLabel"
                >
                    <x-slot name="sidebarToggle">
                        <button
                            type="button"
                            @click="sidebarOpen = true"
                            x-bind:aria-expanded="sidebarOpen"
                            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-border text-muted-foreground transition-colors hover:bg-accent hover:text-foreground lg:hidden"
                            aria-label="Buka menu"
                            aria-controls="app-sidebar"
                        >
                            <x-icon name="menu" class="h-5 w-5" />
                        </button>
                    </x-slot>

                    @isset($headerActions)
                        {{ $headerActions }}
                    @endisset

                    <x-header.profile-menu :display-name="$displayName" />
                </x-layout.header>

                @isset($pageHeader)
                    <x-layout.page-header :max-width="$maxWidth">
                        {{ $pageHeader }}
                    </x-layout.page-header>
                @endisset

                <main class="flex-1">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
