@props([
    'title' => null,
    'displayName' => null,
    'maxWidth' => null,
    'stickyHeader' => false,
    'sidebar' => null,
])

@php
    $user = auth()->user();
    $displayName = $displayName ?? $user->profileName();
    $isDonor = $user->hasRole(\App\Enums\Role::Donor);
    $isRecipient = $user->hasRole(\App\Enums\Role::Penerima);
    $roleLabel = $isDonor ? 'Donor Hub' : ($isRecipient ? 'Penerima / Yayasan' : null);
    $sidebarId = $user->hasRole(\App\Enums\Role::Administrator) ? 'admin-sidebar' : 'app-sidebar';
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
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-background font-sans text-foreground antialiased">
        <div
            class="flex min-h-screen"
            x-data="{ sidebarOpen: false, navQuery: '' }"
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
            @elseif ($isDonor || $isRecipient)
                <x-layout.sidebar id="app-sidebar" :label="$isDonor ? 'Navigasi donor' : 'Navigasi penerima'">
                    @if ($isDonor)
                        <x-donor.sidebar />
                    @else
                        <x-recipient.sidebar />
                    @endif
                </x-layout.sidebar>
            @else
                <x-admin.sidebar />
            @endif

            <div class="flex min-w-0 flex-1 flex-col bg-background p-3 lg:p-[1.375rem] lg:pl-0">
                <div data-content-surface class="flex min-h-[calc(100vh-1.5rem)] flex-1 flex-col border border-border bg-card lg:min-h-[calc(100vh-2.75rem)] lg:rounded-xl">
                    <x-layout.header
                        :title="$title"
                        :role-label="$roleLabel"
                        :display-name="$displayName"
                        :max-width="$maxWidth"
                        :sticky="$stickyHeader"
                    >
                        <x-slot name="sidebarToggle">
                            <button
                                type="button"
                                @click="sidebarOpen = true"
                                x-bind:aria-expanded="sidebarOpen"
                                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-border text-muted-foreground transition-colors hover:bg-accent hover:text-foreground lg:hidden"
                                aria-label="Buka menu"
                                aria-controls="{{ $sidebarId }}"
                            >
                                <x-icon name="menu" class="h-5 w-5" />
                            </button>
                        </x-slot>

                        @isset($headerActions)
                            <x-slot name="actions">
                                {{ $headerActions }}
                            </x-slot>
                        @endisset

                        @isset($status)
                            <x-slot name="status">
                                {{ $status }}
                            </x-slot>
                        @endisset
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
        </div>
    </body>
</html>
