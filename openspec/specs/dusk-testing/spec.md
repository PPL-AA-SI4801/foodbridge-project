# dusk-testing Specification

## Purpose

Menetapkan aturan baku Dusk agar browser test berjalan di database terpisah yang skemanya identik dengan produksi dan datanya menetap untuk diinspeksi.

## Requirements

### Requirement: Dusk memakai database terpisah
Sistem SHALL menjalankan seluruh Dusk browser test terhadap `foodbridge_dusk` dan tidak pernah menulis ke `foodbridge` maupun database produksi.

#### Scenario: Dusk menulis ke DB test
- **WHEN** Dusk dijalankan dengan `--env=dusk.local`
- **THEN** `users`, `donor_profiles`, `recipient_profiles`, dan `verification_documents` baru tercatat di `foodbridge_dusk`.

#### Scenario: DB asli tidak tersentuh
- **WHEN** Dusk selesai berjalan
- **THEN** jumlah baris di `foodbridge` tidak bertambah akibat test.

### Requirement: Skema DB test identik dengan DB asli
Skema `foodbridge_dusk` SHALL berasal dari migration yang sama dengan DB asli sehingga tabel, kolom, dan constraint identik tanpa duplikasi skema manual.

#### Scenario: Migrasi menghasilkan skema sama
- **WHEN** `php artisan migrate --env=dusk.local` dijalankan pada DB kosong
- **THEN** `SHOW TABLES` dan `migrate:status --env=dusk.local` sama dengan DB asli (17 migration).

### Requirement: Data test menetap setelah run
Test Dusk untuk autentikasi, verifikasi, dan profil SHALL NOT melakukan `migrate:fresh`, `migrate:rollback`, truncation, atau seeder cleanup sehingga tabel dan data tetap ada setelah run.

#### Scenario: Tabel tetap ada setelah Dusk
- **WHEN** `AuthenticationTest`, `AccountVerificationTest`, dan `ProfileManagementTest` selesai
- **THEN** `foodbridge_dusk` masih memuat semua tabel aplikasi selain `migrations`.

#### Scenario: Data dapat diinspeksi
- **WHEN** run selesai
- **THEN** baris baru dapat dilihat via `SELECT email,role,status_akun FROM users ORDER BY id DESC`.

### Requirement: Data test unik per-run
Setiap run SHALL memakai email dan nama fixture unik sehingga run berulang tidak gagal duplicate dan tidak bergantung pada DB kosong.

#### Scenario: Run berulang lolos
- **WHEN** Dusk dijalankan dua kali tanpa reset manual
- **THEN** kedua run hijau tanpa error duplicate email.

#### Scenario: Assertion tidak bergantung DB kosong
- **WHEN** DB test sudah berisi data run lama
- **THEN** assertion memakai email/nama unik run berjalan dan tidak false-positive.

### Requirement: Aturan testing terdokumentasi
Proyek SHALL menyediakan `docs/TEST_RULE.md` sebagai acuan tunggal perintah serve, migrate, run, reset, dan larangan DB, serta merevisi aturan DB-test di `CONTRIBUTING.md` agar konsisten.

#### Scenario: Kontributor mengikuti dokumen
- **WHEN** kontributor baru membaca `docs/TEST_RULE.md`
- **THEN** ia dapat menjalankan serve, migrate, Dusk, dan reset tanpa menulis ke DB asli.
