@props(['title' => 'FoodBridge'])

<x-layout.app-shell :title="$title">
    {{ $slot }}
</x-layout.app-shell>
