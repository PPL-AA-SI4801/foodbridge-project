@props(['title' => null, 'pendingVerifications' => 0, 'displayName' => null])

<x-app-layout
    :title="$title"
    :display-name="$displayName"
    :pending-verifications="$pendingVerifications"
    padded
>
    @isset($actions)
        <x-slot name="actions">
            {{ $actions }}
        </x-slot>
    @endisset

    @isset($header)
        <x-slot name="header">
            {{ $header }}
        </x-slot>
    @endisset

    {{ $slot }}
</x-app-layout>
