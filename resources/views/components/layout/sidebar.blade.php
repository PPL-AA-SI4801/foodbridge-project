@props([
    'id' => 'app-sidebar',
    'label' => 'Navigasi utama',
])

<aside
    {{ $attributes->class(['fixed inset-y-0 left-0 z-50 flex w-[16.875rem] max-w-[85vw] shrink-0 flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground transition-transform duration-200 ease-out', 'lg:sticky lg:bottom-auto lg:top-0 lg:z-auto lg:h-screen lg:w-[16.875rem] lg:max-w-none']) }}
    id="{{ $id }}"
    aria-label="{{ $label }}"
    x-bind:class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    x-cloak
>
    {{ $slot }}

    @isset($footer)
        <div class="border-t border-sidebar-border p-3">
            {{ $footer }}
        </div>
    @endisset
</aside>
