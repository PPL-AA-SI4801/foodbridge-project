@props(['status'])

@php
    $variants = [
        'menunggu' => ['class' => 'status--waiting', 'icon' => 'clock', 'label' => 'Menunggu'],
        'disetujui' => ['class' => 'status--approved', 'icon' => 'check-circle', 'label' => 'Disetujui'],
        'selesai' => ['class' => 'status--done', 'icon' => 'check-circle', 'label' => 'Selesai'],
        'ditolak' => ['class' => 'status--rejected', 'icon' => 'x', 'label' => 'Ditolak'],
    ];

    $variant = $variants[$status] ?? $variants['menunggu'];
@endphp

<span {{ $attributes->class(['status', $variant['class']]) }}>
    <x-icon :name="$variant['icon']" :size="13" />
    {{ $variant['label'] }}
</span>
