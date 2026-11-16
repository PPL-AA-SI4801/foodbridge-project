@props(['displayName' => null])

@php
    $menuName = filled($displayName) ? $displayName : auth()->user()->nama;
@endphp

{{-- Menu profil: Profil Saya (FR-02, jika tersedia) dan Logout. --}}
<div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
    <button
        type="button"
        @click="open = ! open"
        x-bind:aria-expanded="open"
        aria-haspopup="true"
        aria-controls="profile-menu-dropdown"
        aria-label="Menu profil {{ $menuName }}"
        class="flex items-center gap-3 rounded-[8px] border border-[#e8e9e8] bg-white p-0 transition-colors hover:bg-sidebar-accent"
    >
        <img
            src="{{ asset('icons/premium.svg') }}"
            alt=""
            class="h-[42px] w-[42px] shrink-0"
        >

        <div class="flex min-w-0 flex-col gap-[5px] pr-3">
            <span class="truncate text-[14px] font-semibold leading-[normal] text-[#0d1c2e]">
                {{ $menuName }}
            </span>
            <span class="truncate text-[12px] font-normal leading-[1.5] text-sidebar-muted">
                {{ auth()->user()->email }}
            </span>
        </div>

        <x-icon name="chevron-down" class="mr-3 h-4 w-4 shrink-0 text-sidebar-muted" />
    </button>

    <div id="profile-menu-dropdown" x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 z-50 mt-2">
        <div class="w-64 overflow-hidden rounded-md border border-border bg-popover p-1 text-popover-foreground">
            <div class="flex flex-col gap-0.5 px-3 py-2.5">
                <span class="truncate text-sm font-medium">{{ auth()->user()->nama }}</span>
                <span class="truncate text-xs text-muted-foreground">{{ auth()->user()->email }}</span>
            </div>

            <div class="my-1 h-px bg-border" aria-hidden="true"></div>

            @if (\Illuminate\Support\Facades\Route::has('profile.edit') && auth()->user()->hasRole(\App\Enums\Role::Donor, \App\Enums\Role::Penerima))
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 rounded-sm px-3 py-2 text-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                    <x-icon name="user" class="h-4 w-4" />
                    <span>Profil Saya</span>
                </a>
            @endif

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button type="submit" class="flex w-full items-center gap-2 rounded-sm px-3 py-2 text-left text-sm transition-colors hover:bg-accent hover:text-destructive">
                    <x-icon name="log-out" class="h-4 w-4" />
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </div>
</div>
