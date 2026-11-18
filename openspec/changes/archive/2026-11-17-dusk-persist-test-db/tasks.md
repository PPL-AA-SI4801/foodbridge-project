# Tasks

## 1. Siapkan DB test terpisah

- [x] 1.1 Buat `foodbridge_dusk` (`utf8mb4/utf8mb4_unicode_ci`) dan jalankan `php artisan migrate --env=dusk.local`, verifikasi dengan `migrate:status --env=dusk.local` sama dengan `migrate:status` (17 migration).
- [x] 1.2 Verifikasi `SHOW TABLES FROM foodbridge_dusk` sama dengan `foodbridge` dan `.env.dusk.local` tidak menunjuk ke DB asli, verifikasi dengan membandingkan output kedua `SHOW TABLES`.

## 2. Konversi 3 file Dusk ke data menetap

- [x] 2.1 Hapus `use DatabaseMigrations` + import-nya dari `AuthenticationTest`, `AccountVerificationTest`, `ProfileManagementTest`, verifikasi dengan `grep -r DatabaseMigrations tests/Browser/AuthenticationTest.php tests/Browser/AccountVerificationTest.php tests/Browser/ProfileManagementTest.php` kosong.
- [x] 2.2 Ganti email/nama fixture statis jadi unik per-run di semua helper (`createDonor/Recipient/Pending/Admin`) dan input form register/login, verifikasi dengan `php artisan dusk` hijau dua kali berturut-turut tanpa reset.
- [x] 2.3 Perbaiki TC-2-05 (`assertEquals(1)` → `assertDatabaseHas` email unik) dan TC-3-02 (`assertDontSee` nama unik run), verifikasi dengan run filter `AuthenticationTest` dan `AccountVerificationTest` hijau di DB yang sudah berisi data lama.
- [x] 2.4 Pastikan alur UI real tetap (register + `fixtures/legalitas.pdf`, login/logout form, approve lalu login donor, request-documents lalu re-upload TC-3-04b) dan cleanup `Storage::delete()` dipertahankan, verifikasi dengan `SELECT` baris baru di `foodbridge_dusk.users` dan file pengganti tidak menumpuk.

## 3. Dokumentasikan aturan

- [x] 3.1 Tulis `docs/TEST_RULE.md` (DB mana untuk apa, `CREATE DATABASE`, `migrate/serve --env=dusk.local`, `php artisan dusk [--filter]`, query inspeksi `SELECT`, reset `migrate:fresh --env=dusk.local`, larangan tulis ke `foodbridge`/prod, kewajiban data unik, catatan `storage/app/` berbagi, `php artisan test` tetap sqlite memory), verifikasi dengan menjalankan setiap perintah di dokumen apa adanya hingga Dusk hijau.
- [x] 3.2 Revisi aturan DB-test di `CONTRIBUTING.md` agar menunjuk ke `docs/TEST_RULE.md` (DB terpisah, data boleh menetap, reset manual), verifikasi dengan membaca ulang kedua dokumen tanpa kontradiksi.

## 4. Verifikasi integrasi

- [x] 4.1 Jalankan `php artisan serve --env=dusk.local` + `php artisan dusk` penuh untuk 3 file dan `php artisan test`, verifikasi dengan Dusk hijau, `php artisan test` hijau, dan `SHOW TABLES`/`SELECT` di `foodbridge_dusk` menunjukkan tabel + data menetap.
