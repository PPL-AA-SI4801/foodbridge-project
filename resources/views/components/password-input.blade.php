@props([
    'id' => 'password',
    'name' => 'password',
    'autocomplete' => 'current-password',
    'required' => false,
])

@php
    $showPasswordLabel = __('Tampilkan kata sandi');
    $hidePasswordLabel = __('Sembunyikan kata sandi');
@endphp

<div class="relative" x-data="{ show: false }">
    <input id="{{ $id }}" name="{{ $name }}" type="password" :type="show ? 'text' : 'password'"
            @required($required) autocomplete="{{ $autocomplete }}"
            {{ $attributes->merge(['class' => 'block w-full rounded-md border border-input bg-white py-2.5 pl-3.5 pr-11 text-sm text-foreground shadow-none transition-[border-color,box-shadow] duration-150 ease-[cubic-bezier(0.23,1,0.32,1)] placeholder:text-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary']) }} />

    <button type="button"
            @click="show = !show"
            :aria-label="show ? @js($hidePasswordLabel) : @js($showPasswordLabel)"
            :aria-pressed="show"
            class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-md text-muted-foreground transition-colors duration-150 hover:text-foreground focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary">
        <x-icon name="eye" class="h-4 w-4" x-show="show" x-cloak />
        <x-icon name="eye-off" class="h-4 w-4" x-show="!show" />
    </button>
</div>
