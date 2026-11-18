@props([
    'title' => null,
    'displayName' => null,
    'pendingVerifications' => null,
    'padded' => false,
])

@php
    $user = auth()->user();
    $isAdmin = $user?->hasRole(\App\Enums\Role::Administrator);
    $isRecipient = $user?->hasRole(\App\Enums\Role::Penerima);

    if (is_null($pendingVerifications) && $isAdmin) {
        $pendingVerifications = \App\Models\User::query()->pendingVerification()->count();
    }

    $dailyQuotaStatus = null;

    if ($isRecipient) {
        $recipientProfile = $user->recipientProfile;
        $quota = $recipientProfile
            ? app(\App\Services\RecipientClaimService::class)->quotaSummary($recipientProfile->id)
            : null;
        $dailyQuotaStatus = $quota
            ? $quota['remaining'].'/'.$quota['limit']
            : null;
    }
@endphp

<x-layout.app-shell :title="$title" :display-name="$displayName" :padded="$padded">
    @isset($header)
        <x-slot name="pageHeader">
            {{ $header }}
        </x-slot>
    @endisset

    @if (! is_null($pendingVerifications) && $isAdmin)
        <x-slot name="status">
            <span class="flex h-9 items-center gap-2 rounded-[8px] border border-[#e8e9e8] bg-white px-2.5 text-[12px] font-normal text-sidebar-muted">
                <x-icon name="user-check" class="h-4 w-4 shrink-0" />
                <span>Menunggu Verifikasi:</span>
                <span class="font-bold text-[#0d1c2e]">{{ $pendingVerifications }}</span>
            </span>
        </x-slot>
    @elseif ($dailyQuotaStatus)
        <x-slot name="status">
            <span class="flex h-9 items-center gap-2 rounded-[8px] border border-[#e8e9e8] bg-white px-2.5 text-[12px] font-normal text-sidebar-muted">
                <x-icon name="package" class="h-4 w-4 shrink-0" />
                <span>Kuota Harian:</span>
                <span class="font-bold text-[#0d1c2e]">{{ $dailyQuotaStatus }}</span>
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
