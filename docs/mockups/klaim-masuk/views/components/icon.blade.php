@props(['name', 'size' => 20])

@php
    $icons = [
        'flame' => '<path d="M12 2.5c2.9 2.6 5.5 6.2 5.5 9.4a5.5 5.5 0 0 1-11 0c0-1.9.9-3.5 2.1-4.7.2 1.7 1.1 2.7 2 2.7.1-2.9-1.2-5.2-.6-7.4Z"/><path d="M9.8 16.6c0 1.4 1 2.4 2.4 2.4s2.4-1 2.4-2.4"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.6-3.6"/>',
        'home' => '<path d="m3 10.5 9-7.5 9 7.5"/><path d="M5.5 9.5V21h13V9.5"/><path d="M9.5 21v-6h5v6"/>',
        'utensils' => '<path d="M3 2v7a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6a2 2 0 0 0 2 2h3Zm0 0v7"/>',
        'inbox' => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.4 5.1 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.4-6.9A2 2 0 0 0 16.8 4H7.2a2 2 0 0 0-1.8 1.1Z"/>',
        'clipboard' => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M9 12h6M9 16h6"/>',
        'star' => '<path d="m12 3 2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17.8 6.2 20.9l1.1-6.5L2.6 9.8l6.5-.9Z"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'bell' => '<path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
        'file-text' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h6"/>',
        'arrow-left' => '<path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>',
        'arrow-right' => '<path d="m12 5 7 7-7 7"/><path d="M5 12h14"/>',
        'box' => '<path d="m21 8-9-5-9 5v8l9 5 9-5Z"/><path d="M3.3 7.6 12 12.5l8.7-4.9"/><path d="M12 21.5v-9"/>',
        'bread' => '<path d="M3.5 11h17"/><path d="M5 11a7 7 0 0 1 14 0"/><path d="M4 14.5h16a2 2 0 0 1-2 3.5H6a2 2 0 0 1-2-3.5Z"/><path d="M8 7v-2M12 6V4M16 7V5"/>',
        'meal' => '<circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="3.2"/><path d="M12 3.5v2M12 18.5v2M3.5 12h2M18.5 12h2"/>',
        'bowl' => '<path d="M3 11h18a9 9 0 0 1-18 0Z"/><path d="M6.5 11c0-2 1-3.6 2.2-4.4"/><path d="M11 11c0-2.6 1.2-4.4 2.6-5.4"/><path d="M7 20h10"/>',
        'walk' => '<circle cx="13" cy="4" r="2"/><path d="m9 21 2.5-6-2-3.5L11 7l3 2 3 1"/><path d="m14 15 2.5 6"/>',
        'motor' => '<circle cx="5.5" cy="17.5" r="3.5"/><circle cx="18.5" cy="17.5" r="3.5"/><path d="M5.5 17.5 9.5 9h4.5l4.5 8.5"/><path d="M9 9h5"/><path d="m12.5 9-1.5 4"/>',
        'x' => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'alert' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5h.01"/>',
    ];

    $paths = $icons[$name] ?? $icons['alert'];
@endphp

<svg
    xmlns="http://www.w3.org/2000/svg"
    width="{{ $size }}"
    height="{{ $size }}"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.7"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    {{ $attributes }}
>{!! $paths !!}</svg>
