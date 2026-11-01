<x-layout.app-shell>
    @isset($header)
        <x-slot name="pageHeader">
            {{ $header }}
        </x-slot>
    @endisset

    {{ $slot }}
</x-layout.app-shell>
