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
            <span class="flex h-10 items-center gap-3 rounded-[8px] border border-[#e8e9e8] bg-white px-3 text-[14px] font-normal text-sidebar-muted">
                <x-icon name="user-check" class="h-[18px] w-[18px] shrink-0" />
                <span>Menunggu Verifikasi:</span>
                <span class="font-bold text-[#0d1c2e]">{{ $pendingVerifications }}</span>
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
