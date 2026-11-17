# Proposal

## Why

Dusk browser test saat ini memakai `DatabaseMigrations` sehingga `foodbridge_dusk` di-`migrate:fresh` di awal lalu `migrate:rollback` di akhir — hasilnya hanya tersisa tabel `migrations` dan data tidak bisa diinspeksi. Aturan `CONTRIBUTING.md` yang mewajibkan seeder idempoten + fresh migrate per-run bertentangan dengan kebutuhan inspeksi data test secara langsung.

## What Changes

- Hapus trait `DatabaseMigrations` dari `AuthenticationTest`, `AccountVerificationTest`, `ProfileManagementTest` agar skema + data di `foodbridge_dusk` menetap setelah run.
- Ganti semua email/nama fixture statis menjadi unik per-run (`time()` + `Str::random()`) agar run berulang tidak gagal duplicate.
- Tetapkan `foodbridge_dusk` sebagai satu-satunya DB Dusk (`--env=dusk.local` untuk `serve` dan `migrate`); skema dijamin identik via 17 migration yang sama, tanpa duplikasi skema manual.
- Perbaiki assertion yang mengasumsikan DB kosong (TC-2-05 count, TC-3-02 `assertDontSee`).
- Tambah `docs/TEST_RULE.md` sebagai aturan baku testing Dusk (dibuat saat apply, draft diatur di design).
- Revisi aturan merge DB-test di `CONTRIBUTING.md` (DB terpisah, data boleh menetap, reset manual).

## Capabilities

### New Capabilities

- `dusk-testing`: aturan baku Dusk persistent test-DB — DB terpisah, skema identik via migrasi, data unik per-run dan dapat diinspeksi, perintah serve/migrate/reset yang benar.

### Modified Capabilities

- Tidak ada. `mysql-default-config` mengatur DB utama, tidak berubah.

## Impact

- Affected: `tests/Browser/AuthenticationTest.php`, `tests/Browser/AccountVerificationTest.php`, `tests/Browser/ProfileManagementTest.php`, `.env.dusk.local`, `CONTRIBUTING.md`, baru: `docs/TEST_RULE.md`, `openspec/specs/dusk-testing/spec.md`.
- Tidak menyentuh: `AccountBlockingTest.php`, `phpunit.xml` (sqlite memory untuk `php artisan test`), app code `app/`, `routes/`, `config/database.php`.
- Risiko: `foodbridge_dusk` membengkak antar run; perlu `migrate:fresh --env=dusk.local` manual berkala. Upload fixture PDF berbagi `storage/app/` dengan dev.
