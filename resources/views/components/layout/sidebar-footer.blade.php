@props([
    'name' => null,
    'email' => null,
    'showLogout' => true,
])

@php
    $user = auth()->user();
    $displayName = $name ?? $user->profileName() ?? $user->nama;
    $displayEmail = $email ?? $user->email;
@endphp

<div {{ $attributes->class(['mt-auto p-4']) }}>
    <div class="flex flex-col gap-[10px] rounded-[10px] border border-[#e8e9e8] bg-white p-[12px]">
        <div class="flex items-center gap-3 overflow-clip">
            <img
                src="{{ asset('icons/premium.svg') }}"
                alt=""
                class="h-[42px] w-[42px] shrink-0"
            >

            <div class="flex min-w-0 flex-1 flex-col gap-[5px]">
                <span class="truncate text-[14px] font-semibold leading-[normal] text-[#0d1c2e]">
                    {{ $displayName }}
                </span>
                <span class="truncate text-[12px] font-normal leading-[1.5] text-sidebar-muted">
                    {{ $displayEmail }}
                </span>
            </div>
        </div>

        @if ($showLogout)
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button
                    type="submit"
                    class="flex h-10 w-full items-center justify-center gap-3 rounded-[8px] border border-[#e8e9e8] bg-white px-3 text-[14px] font-normal text-sidebar-muted transition-colors hover:bg-sidebar-accent"
                >
                    <x-icon name="log-out" class="h-[18px] w-[18px] shrink-0" />
                    <span>Logout</span>
                </button>
            </form>
        @endif
    </div>
</div>
