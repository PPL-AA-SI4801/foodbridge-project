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
        $dailyClaimsCount = $recipientProfile
            ? \App\Models\Claim::query()
                ->where('recipient_id', $recipientProfile->id)
                ->whereDate('tanggal_klaim', today())
                ->count()
            : 0;
        $dailyQuotaLimit = 3;
        $dailyQuotaStatus = $dailyClaimsCount.'/'.$dailyQuotaLimit;
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
            <span class="flex h-10 items-center gap-3 rounded-[8px] border border-[#e8e9e8] bg-white px-3 text-[14px] font-normal text-sidebar-muted">
                <x-icon name="user-check" class="h-[18px] w-[18px] shrink-0" />
                <span>Menunggu Verifikasi:</span>
                <span class="font-bold text-[#0d1c2e]">{{ $pendingVerifications }}</span>
            </span>
        </x-slot>
    @elseif ($dailyQuotaStatus)
        <x-slot name="status">
            <span class="flex h-10 items-center gap-3 rounded-[8px] border border-[#e8e9e8] bg-white px-3 text-[14px] font-normal text-sidebar-muted">
                <x-icon name="package" class="h-[18px] w-[18px] shrink-0" />
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
