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

<div {{ $attributes->class(['mt-auto p-3 lg:p-4']) }}>
    <div class="rounded-xl border border-sidebar-border bg-card p-3">
        <div class="flex items-center gap-3">
            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-semibold text-primary-foreground">
                {{ str($displayName)->substr(0, 1)->upper() }}
            </span>

            <div class="flex min-w-0 flex-col">
                <span class="truncate text-sm font-semibold text-foreground">{{ $displayName }}</span>
                <span class="truncate text-xs text-muted-foreground">{{ $displayEmail }}</span>
            </div>
        </div>

        @if ($showLogout)
            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf

                <button
                    type="submit"
                    class="flex w-full items-center gap-2 rounded-lg px-2.5 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                >
                    <x-icon name="log-out" class="h-4 w-4" />
                    <span>Logout</span>
                </button>
            </form>
        @endif
    </div>
</div>
