@props(['sidebar' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @if (isset($sidebar))
                <x-layout.sidebar>
                    {{ $sidebar }}
                </x-layout.sidebar>

                <div class="lg:pl-64 flex flex-col min-h-screen">
                    @if (isset($header))
                        {{ $header }}
                    @elseif (isset($headerSlot))
                        <x-layout.header>
                            {{ $headerSlot }}
                        </x-layout.header>
                    @else
                        @include('layouts.navigation')
                    @endif

                    @isset($pageHeader)
                        <header class="bg-white shadow">
                            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                                {{ $pageHeader }}
                            </div>
                        </header>
                    @endisset

                    <main class="flex-1">
                        {{ $slot }}
                    </main>
                </div>
            @else
                @include('layouts.navigation')

                @isset($pageHeader)
                    <header class="bg-white shadow">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $pageHeader }}
                        </div>
                    </header>
                @endisset

                <main>
                    {{ $slot }}
                </main>
            @endif
        </div>
    </body>
</html>
