@php
    $isAdmin = $isAdmin ?? auth()->user()->hasRole(\App\Enums\Role::Administrator);
@endphp

@if ($isAdmin)
    <x-admin-layout title="Notifikasi" :pending-verifications="$pendingVerifications ?? 0">
        @include('notifications._content')
    </x-admin-layout>
@else
    <x-app-layout>
        <div class="px-4 py-8 sm:px-6 lg:px-8">
            @include('notifications._content')
        </div>
    </x-app-layout>
@endif
