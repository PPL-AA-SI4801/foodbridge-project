# FoodBridge Design System

Panduan visual untuk antarmuka FoodBridge (Laravel Blade + shadcn). Semua komponen memakai shadcn yang sudah terinstall dengan tampilan standar/default. Token di bawah ini menimpa variabel tema shadcn.

---

## 1. Prinsip

| Prinsip | Aturan |
|---|---|
| Mode | Light mode saja, tanpa dark mode |
| Gaya | Flat: tanpa shadow, pemisah antar elemen memakai border 1px |
| Kerapatan | Lega: padding dan jarak antar elemen dibuat longgar |
| Bahasa konten | Bahasa Indonesia untuk semua teks antarmuka |
| Komponen | Standar/default shadcn, tanpa modifikasi gaya dulu |
| Ikon | Lucide (bawaan shadcn) |
| Notifikasi in-app | Toast |
| Aksesibilitas | Default shadcn (kontras dan focus ring bawaan) |

---

## 2. Referensi Visual

Pola yang diambil dari gambar referensi:

- **Header halaman:** judul di kiri dengan subjudul kecil di bawahnya (contoh: "Selamat datang, Budi"), aksi di kanan (ikon notifikasi, tombol utama berwarna primary gelap).
- **Kartu statistik:** label kecil huruf kapital berwarna muted, angka besar tebal, badge persentase (naik/turun), dan ikon di dalam kotak ber-border di pojok kanan.
- **Kartu:** background terang, border tipis, sudut membulat sedang, jarak antar kartu lega.
- **Tabel:** berada di dalam kartu, dengan kolom pencarian dan tombol aksi (Refresh/Ekspor) di atasnya.
- **Tombol utama:** warna primary gelap dengan teks putih.

Sidebar pada gambar referensi (hijau gelap) belum diadopsi, lihat bagian 8.

---

## 3. Warna

### 3.1 Token Inti

| Token | Hex | Keterangan |
|---|---|---|
| `--background` | `#F7F8F6` | Background halaman (off-white) |
| `--foreground` | `#111816` | Warna teks utama |
| `--card` | `#FCFCFB` | Surface kartu, sedikit lebih terang dari background agar terbaca |
| `--card-foreground` | `#111816` | Teks di dalam kartu |
| `--popover` | `#FCFCFB` | Dropdown, dialog, toast |
| `--popover-foreground` | `#111816` | Teks di dalam popover |
| `--primary` | `#133830` | Warna utama: tombol utama, link, fokus |
| `--primary-foreground` | `#FFFFFF` | Teks di atas primary |
| `--secondary` | `#D97736` | Warna sekunder: tombol sekunder, aksen |
| `--secondary-foreground` | `#111816` | Teks di atas secondary (gelap, lihat catatan) |
| `--muted` | `#EFF1ED` | Background area redup (header tabel, skeleton) |
| `--muted-foreground` | `#6B746E` | Teks sekunder, placeholder, label kecil |
| `--accent` | `#EFF1ED` | Background hover item (menu, row) |
| `--accent-foreground` | `#111816` | Teks pada state hover |
| `--destructive` | `#E7000B` | Aksi berbahaya (hapus, tolak) |
| `--destructive-foreground` | `#FFFFFF` | Teks di atas destructive |
| `--border` | `#E6E9E4` | Border: abu hampir sama dengan off-white |
| `--input` | `#DDE1DB` | Border input, sedikit lebih terlihat dari border biasa |
| `--ring` | `#133830` | Focus ring |

### 3.2 Catatan

- **Muted text** memakai nada yang sama dengan border tetapi lebih gelap (`#6B746E`), kontrasnya sekitar 4,5:1 terhadap background.
- **Teks di atas secondary** harus gelap (`#111816`). Teks putih di atas `#D97736` hanya sekitar 3,2:1 sehingga kurang terbaca.
- Karena `--secondary` berwarna oranye, komponen shadcn yang memakai variant `secondary` (Button, Badge) ikut menjadi oranye. State hover umum memakai `--accent`, bukan secondary.

### 3.3 Warna Chart

| Token | Hex |
|---|---|
| `--chart-1` | `#133830` |
| `--chart-2` | `#D97736` |
| `--chart-3` | `#4A9B86` |
| `--chart-4` | `#E8B77A` |
| `--chart-5` | `#6B746E` |

---

## 4. Badge Status

Setiap status memiliki warna sendiri dan tidak ada yang sama atau mirip. Gaya default adalah *soft* (background pucat, teks gelap, border tipis). Dua status memakai gaya *solid* agar jelas berbeda dari yang lain.

| Status | Dipakai di | Gaya | Background | Teks | Border |
|---|---|---|---|---|---|
| Menunggu | Klaim, verifikasi akun | Soft kuning | `#FEF9C3` | `#854D0E` | `#FDE047` |
| Disetujui | Klaim, verifikasi akun | Soft biru | `#DBEAFE` | `#1E40AF` | `#93C5FD` |
| Ditolak | Klaim, verifikasi akun | Soft merah | `#FEE2E2` | `#B91C1C` | `#FCA5A5` |
| Selesai | Klaim | Soft hijau | `#DCFCE7` | `#166534` | `#86EFAC` |
| Dibatalkan | Klaim | Soft ungu | `#F3E8FF` | `#6B21A8` | `#D8B4FE` |
| Kedaluwarsa | Postingan makanan | Soft abu | `#E5E7EB` | `#374151` | `#D1D5DB` |
| Ditinjau | Laporan makanan | Soft pink | `#FCE7F3` | `#9D174D` | `#F9A8D4` |
| Aktif | Postingan makanan | Solid oranye | `#D97736` | `#111816` | `#D97736` |
| Diblokir | Akun | Solid hitam | `#111816` | `#FFFFFF` | `#111816` |

Status baru ditambahkan dengan hue yang belum terpakai di tabel ini.

### Contoh komponen Blade

```blade
{{-- resources/views/components/status-badge.blade.php --}}
@props(['status'])

@php
$map = [
    'menunggu'    => ['label' => 'Menunggu',    'class' => 'bg-[#FEF9C3] text-[#854D0E] border-[#FDE047]'],
    'disetujui'   => ['label' => 'Disetujui',   'class' => 'bg-[#DBEAFE] text-[#1E40AF] border-[#93C5FD]'],
    'ditolak'     => ['label' => 'Ditolak',     'class' => 'bg-[#FEE2E2] text-[#B91C1C] border-[#FCA5A5]'],
    'selesai'     => ['label' => 'Selesai',     'class' => 'bg-[#DCFCE7] text-[#166534] border-[#86EFAC]'],
    'dibatalkan'  => ['label' => 'Dibatalkan',  'class' => 'bg-[#F3E8FF] text-[#6B21A8] border-[#D8B4FE]'],
    'kedaluwarsa' => ['label' => 'Kedaluwarsa', 'class' => 'bg-[#E5E7EB] text-[#374151] border-[#D1D5DB]'],
    'ditinjau'    => ['label' => 'Ditinjau',    'class' => 'bg-[#FCE7F3] text-[#9D174D] border-[#F9A8D4]'],
    'aktif'       => ['label' => 'Aktif',       'class' => 'bg-[#D97736] text-[#111816] border-[#D97736]'],
    'diblokir'    => ['label' => 'Diblokir',    'class' => 'bg-[#111816] text-white border-[#111816]'],
];
$item = $map[$status] ?? ['label' => ucfirst($status), 'class' => 'bg-muted text-foreground border-border'];
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium', $item['class']]) }}>
    {{ $item['label'] }}
</span>
```

Pemakaian: `<x-status-badge status="disetujui" />`

---

## 5. Tipografi

| Properti | Nilai |
|---|---|
| Font | Inter, fallback `ui-sans-serif, system-ui, sans-serif` (satu font untuk heading dan body) |
| Skala ukuran | Default Tailwind (`text-xs` sampai `text-3xl`) |

Pola teks yang sering dipakai:

| Kegunaan | Kelas Tailwind |
|---|---|
| Judul halaman | `text-2xl font-semibold` |
| Subjudul halaman | `text-sm text-muted-foreground` |
| Label kartu (huruf kapital) | `text-xs font-medium uppercase tracking-wide text-muted-foreground` |
| Angka statistik | `text-3xl font-semibold` |
| Teks isi | `text-sm` |

---

## 6. Bentuk dan Jarak

| Properti | Nilai |
|---|---|
| Border radius | Sedang: `--radius: 0.5rem` |
| Border | 1px, warna `--border` |
| Shadow | Tidak dipakai |
| Padding kartu | `p-6` |
| Jarak antar kartu / section | `gap-6` |
| Jarak antar field form | `gap-4` hingga `gap-6` |
| Padding halaman | `p-6` (mobile), `lg:p-8` (desktop) |

---

## 7. Layout dan Responsif

| Properti | Nilai |
|---|---|
| Desktop | Mulai breakpoint `lg` (1024px) ke atas |
| Mobile | Di bawah `lg`, termasuk tablet |
| Bottom navigation Penerima | Tampil di bawah `lg`, menggantikan sidebar |
| Lebar sidebar, tinggi header, max-width konten | Ditentukan kemudian |

---

## 8. Ditentukan Kemudian

Bagian berikut sengaja dilewati dulu dan akan diisi pada iterasi berikutnya:

- Gaya sidebar (state aktif, warna, lebar)
- Rasio foto makanan dan ilustrasi empty state
- Gaya loading (skeleton/spinner)
- Komponen custom di luar shadcn (Food Card, Stat Card, input kode 6 digit, uploader foto)

---

## 9. Komponen

Gunakan komponen shadcn standar tanpa mengubah gayanya. Ikon diambil dari Lucide.

Notifikasi in-app ditampilkan sebagai **toast** (komponen Sonner/Toast bawaan shadcn).

---

## 10. CSS Variables

Tempel di file CSS utama (misalnya `resources/css/app.css`). Sesuaikan format nilai (hex, HSL, atau OKLCH) dengan yang dipakai instalasi shadcn Anda.

```css
:root {
  --background: #F7F8F6;
  --foreground: #111816;

  --card: #FCFCFB;
  --card-foreground: #111816;

  --popover: #FCFCFB;
  --popover-foreground: #111816;

  --primary: #133830;
  --primary-foreground: #FFFFFF;

  --secondary: #D97736;
  --secondary-foreground: #111816;

  --muted: #EFF1ED;
  --muted-foreground: #6B746E;

  --accent: #EFF1ED;
  --accent-foreground: #111816;

  --destructive: #E7000B;
  --destructive-foreground: #FFFFFF;

  --border: #E6E9E4;
  --input: #DDE1DB;
  --ring: #133830;

  --chart-1: #133830;
  --chart-2: #D97736;
  --chart-3: #4A9B86;
  --chart-4: #E8B77A;
  --chart-5: #6B746E;

  --radius: 0.5rem;
}
```

Jika memakai Tailwind v4, petakan token ke tema:

```css
@theme inline {
  --color-background: var(--background);
  --color-foreground: var(--foreground);
  --color-card: var(--card);
  --color-card-foreground: var(--card-foreground);
  --color-popover: var(--popover);
  --color-popover-foreground: var(--popover-foreground);
  --color-primary: var(--primary);
  --color-primary-foreground: var(--primary-foreground);
  --color-secondary: var(--secondary);
  --color-secondary-foreground: var(--secondary-foreground);
  --color-muted: var(--muted);
  --color-muted-foreground: var(--muted-foreground);
  --color-accent: var(--accent);
  --color-accent-foreground: var(--accent-foreground);
  --color-destructive: var(--destructive);
  --color-border: var(--border);
  --color-input: var(--input);
  --color-ring: var(--ring);
  --radius-lg: var(--radius);
  --radius-md: calc(var(--radius) - 2px);
  --radius-sm: calc(var(--radius) - 4px);
}
```