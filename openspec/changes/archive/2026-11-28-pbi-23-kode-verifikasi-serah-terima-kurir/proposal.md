# Proposal

## Why

Klaim `kurir_online` yang sudah approved dan datanya dilaporkan (PBI-11) belum punya titik serah terima yang tercatat: Donor tidak punya cara baku memverifikasi kurir yang datang dan menutup klaim menjadi Selesai. PBI-23 menutup celah ini dengan pola yang sama seperti PBI-12 (serah terima mandiri), tetapi berbasis foto bukti kurir.

## What Changes

- Donor pemilik postingan melihat data pengemudi read-only (platform, nama, nopol, kode verifikasi kurir, screenshot pesanan) pada klaim approved `kurir_online`.
- Donor wajib mengunggah foto kurir (JPG/JPEG/PNG/WEBP, maks 2 MB) lalu menekan "Makanan sudah diserahkan".
- Sistem menyimpan foto ke `courier_details.foto_serah_terima`, mengubah status menjadi `completed`, mengisi `completed_at`, dan mengirim notifikasi "Makanan Telah Diserahkan" ke Penerima — atomik dalam satu transaksi, sekali saja.
- Visibilitas dibatasi: foto + data pengemudi hanya untuk Donor pemilik; Donor lain 403; Penerima/notifikasi/admin tidak melihat foto. Admin hanya melihat status, metode, waktu serah terima.
- Setelah selesai: badge "Diserahkan pada ..." tampil di Donor dan Penerima, tombol hilang, foto tidak dapat diubah, notifikasi mengarah ke Klaim Saya.

## Capabilities

### New Capabilities

- `courier-handover`: serah terima klaim kurir_online oleh Donor dengan foto bukti, penyelesaian atomik, dan notifikasi Penerima.

### Modified Capabilities

- Tidak ada. Perilaku PBI-12 (ambil_sendiri + kode 6 digit), PBI-09, dan PBI-11 tidak diubah; hanya dibaca sebagai prasyarat.

## Impact

- Kode: migrasi `foto_serah_terima` nullable di `courier_details`; `CourierDetail` fillable + accessor URL; service handover kurir baru/metode baru di `ClaimHandoverService`; `CompleteCourierHandoverRequest`; `Donor\ClaimController@completeCourier`; route `donor.claims.complete_courier` (URL `complete-courier`); Blade `donor/claims/index` (upload + badge + foto), `recipient/claims/index` (badge waktu), notifikasi `Makanan Telah Diserahkan` ke Klaim Saya.
- Test: Feature `CourierHandoverTest` (TC-23-01 s/d TC-23-10) + Dusk `CourierHandoverTest` mengikuti `docs/TEST_RULE.md` (DB `foodbridge_dusk`, tanpa reset trait, data unik per-run).
- Tidak ada breaking change API publik; kolom baru nullable sehingga data lama tetap valid.
