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
    <div class="flex flex-col gap-2.5 rounded-lg border border-border bg-card p-3">
        <div class="flex items-center gap-3 overflow-clip">
            <img
                src="{{ asset('icons/premium.svg') }}"
                alt=""
                class="h-10 w-10 shrink-0"
            >

            <div class="flex min-w-0 flex-1 flex-col gap-1">
                <span class="truncate text-sm font-semibold leading-normal text-foreground">
                    {{ $displayName }}
                </span>
                <span class="truncate text-xs font-normal leading-normal text-sidebar-muted">
                    {{ $displayEmail }}
                </span>
            </div>
        </div>

        @if ($showLogout)
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button
                    type="submit"
                    class="flex h-10 w-full items-center justify-center gap-3 rounded-lg border border-border bg-card px-3 text-sm font-normal text-sidebar-muted transition-colors hover:bg-sidebar-accent"
                >
                    <x-icon name="log-out" class="h-4 w-4 shrink-0" />
                    <span>Logout</span>
                </button>
            </form>
        @endif
    </div>
</div>
