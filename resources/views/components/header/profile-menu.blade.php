{{-- Menu profil: Profil Saya (FR-02, jika tersedia) dan Logout. --}}
<div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
    <button
        type="button"
        @click="open = ! open"
        x-bind:aria-expanded="open"
        aria-haspopup="true"
        class="flex items-center gap-2 rounded-md px-1.5 py-1 text-sm transition-colors hover:bg-accent"
    >
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-primary text-xs font-medium text-primary-foreground">
            {{ str(auth()->user()->nama)->substr(0, 1)->upper() }}
        </span>
        <span class="hidden text-sm font-medium sm:block">{{ auth()->user()->nama }}</span>
        <x-icon name="chevron-down" class="h-4 w-4 text-muted-foreground" />
    </button>

    <div x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 z-50 mt-2">
        <div class="w-64 overflow-hidden rounded-md border border-border bg-popover p-1 text-popover-foreground">
            <div class="flex flex-col gap-0.5 px-3 py-2.5">
                <span class="truncate text-sm font-medium">{{ auth()->user()->nama }}</span>
                <span class="truncate text-xs text-muted-foreground">{{ auth()->user()->email }}</span>
            </div>

            <div class="my-1 h-px bg-border" aria-hidden="true"></div>

            @if (\Illuminate\Support\Facades\Route::has('profile.edit'))
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
