@props([
    'title' => null,
    'displayName' => null,
    'pendingVerifications' => null,
    'padded' => false,
])

@php
    $pendingVerifications = $pendingVerifications ?? (
        auth()->user()?->hasRole(\App\Enums\Role::Administrator)
            ? \App\Models\User::query()->pendingVerification()->count()
            : null
    );
@endphp

<x-layout.app-shell :title="$title" :display-name="$displayName" :padded="$padded">
    @isset($header)
        <x-slot name="pageHeader">
            {{ $header }}
        </x-slot>
    @endisset

    @if (! is_null($pendingVerifications))
        <x-slot name="status">
            <span class="inline-flex h-9 items-center gap-2 rounded-md border border-border bg-card px-3 text-sm">
                <x-icon name="user-check" class="h-4 w-4 text-muted-foreground" />
                <span class="hidden text-muted-foreground sm:inline">Menunggu Verifikasi:</span>
                <span class="font-semibold">{{ $pendingVerifications }}</span>
            </span>
        </x-slot>
    @endif

    @isset($actions)
        <x-slot name="headerActions">
            {{ $actions }}
        </x-slot>
    @endisset

    {{ $slot }}
</x-layout.app-shell>
