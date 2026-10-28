{{-- Badge status bersama; warna mengikuti docs/design_system.md. --}}
@props(['status'])

@php
$value = $status instanceof \BackedEnum ? $status->value : (string) $status;

$map = [
    'menunggu' => ['label' => 'Menunggu', 'class' => 'bg-[#FEF9C3] text-[#854D0E] border-[#FDE047]'],
    'disetujui' => ['label' => 'Disetujui', 'class' => 'bg-[#DBEAFE] text-[#1E40AF] border-[#93C5FD]'],
    'ditolak' => ['label' => 'Ditolak', 'class' => 'bg-[#FEE2E2] text-[#B91C1C] border-[#FCA5A5]'],
    'selesai' => ['label' => 'Selesai', 'class' => 'bg-[#DCFCE7] text-[#166534] border-[#86EFAC]'],
    'dibatalkan' => ['label' => 'Dibatalkan', 'class' => 'bg-[#F3E8FF] text-[#6B21A8] border-[#D8B4FE]'],
    'kedaluwarsa' => ['label' => 'Kedaluwarsa', 'class' => 'bg-[#E5E7EB] text-[#374151] border-[#D1D5DB]'],
    'ditinjau' => ['label' => 'Ditinjau', 'class' => 'bg-[#FCE7F3] text-[#9D174D] border-[#F9A8D4]'],
    'aktif' => ['label' => 'Aktif', 'class' => 'bg-[#D97736] text-[#111816] border-[#D97736]'],
    'diblokir' => ['label' => 'Diblokir', 'class' => 'bg-[#111816] text-white border-[#111816]'],
];

$item = $map[$value] ?? ['label' => ucfirst($value), 'class' => 'bg-muted text-foreground border-border'];
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium', $item['class']]) }}>
    {{ $item['label'] }}
</span>
