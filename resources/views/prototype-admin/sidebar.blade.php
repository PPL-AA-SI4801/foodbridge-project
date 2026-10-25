{{--
    Prototype: Admin sidebar, three structurally different variants.

    A  Klasik        - sidebar penuh, grup menu berlabel, footer pengguna.
    B  Rail Ikon     - rail sempit ikon-saja dengan tooltip, tanpa label.
    C  Inset Melayang- panel kartu terpisah dari tepi, pencarian, submenu bertingkat.

    Hanya sidebar; area konten sengaja dibiarkan kosong.
    Styling memakai token shadcn dari docs/design_system.md.
--}}

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>FoodBridge - Prototype Sidebar Admin ({{ $variant }})</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-background font-sans text-foreground antialiased">
        @switch($variant)
            @case('B')
                @include('prototype-admin.variants.variant-b')
                @break
            @case('C')
                @include('prototype-admin.variants.variant-c')
                @break
            @default
                @include('prototype-admin.variants.variant-a')
        @endswitch

        @if (! app()->isProduction())
            @include('prototype-admin.partials.switcher', [
                'variant' => $variant,
                'variants' => $variants,
                'labels' => ['A' => 'Klasik', 'B' => 'Rail Ikon', 'C' => 'Inset Melayang'],
            ])
        @endif
    </body>
</html>
