# Test Rule — Dusk Persistent Test DB

Acuan tunggal testing browser (Dusk) FoodBridge. Aturan merge DB-test di `CONTRIBUTING.md` mengikuti dokumen ini.

## 1. DB mana untuk apa

| Kebutuhan | DB | Config |
|---|---|---|
| Dev / asli | `foodbridge` | `.env` (`DB_DATABASE=foodbridge`) |
| Dusk browser test | `foodbridge_dusk` | `.env.dusk.local:28` (`DB_DATABASE=foodbridge_dusk`) |
| Feature/Unit test | sqlite `:memory:` | `phpunit.xml:26-27` |

Larangan: Dusk tidak boleh menunjuk ke `foodbridge` / prod. Semua `insert` Dusk harus masuk `foodbridge_dusk` saja. `php artisan test` tidak menyentuh MySQL sama sekali.

## 2. Skema dijamin identik

Tidak ada duplikasi skema manual. Kedua DB MySQL dibuat dari 17 migration yang sama di `database/migrations/`. Samakan level server: MySQL versi sama, `charset=utf8mb4`, `collation=utf8mb4_unicode_ci`, engine InnoDB (`config/database.php:56-61`).

Setup sekali:

```sh
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS foodbridge_dusk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate --env=dusk.local
php artisan migrate:status --env=dusk.local
# bandingkan dengan:
php artisan migrate:status
```

Keduanya harus list 17 migration yang sama.

## 3. Data menetap — berlaku untuk semua file Dusk

Aturan umum: semua file di `tests/Browser/*Test.php` tidak boleh memakai trait reset DB apapun — `DatabaseMigrations`, `DatabaseTruncation`, `RefreshDatabase`, maupun `LazilyRefreshDatabase`.

Alasannya: `DatabaseMigrations` melakukan `migrate:fresh` di awal dan `migrate:rollback` di akhir (`beforeApplicationDestroyed`), sehingga setelah run `foodbridge_dusk` tersisa tabel `migrations` saja. Trait truncation/refresh juga menghapus data. Dengan semua trait itu dilepas, tabel + data menetap dan bisa diinspeksi.

Cek cepat sebelum push:

```sh
grep -rn "DatabaseMigrations\|DatabaseTruncation\|RefreshDatabase" tests/Browser/ || echo OK
```

Harus `OK` (tidak ada yang ketemu).

## 4. Cara run yang benar

```sh
# terminal 1 — serve dengan env Dusk agar app baca foodbridge_dusk
php artisan serve --env=dusk.local

# terminal 2
php artisan dusk
php artisan dusk --filter AuthenticationTest
php artisan dusk --filter AccountVerificationTest
php artisan dusk --filter ProfileManagementTest
```

## 5. Inspeksi data via phpMyAdmin

1. Buka phpMyAdmin (misal `http://localhost/phpmyadmin`).
2. Pilih database `foodbridge_dusk` di panel kiri.
3. Buka tabel `users` → tab Browse — pastikan baris baru dari run tadi ada.
4. Ulangi untuk tabel terkait (`donor_profiles`, `recipient_profiles`, `verification_documents`).
5. Bandingkan struktur: tab Structure `foodbridge` vs `foodbridge_dusk` harus sama (nama tabel/kolom sama, beda hanya isi data). Jika `foodbridge_dusk` hanya berisi `migrations`, berarti migrasi belum dijalankan dengan `--env=dusk.local` atau trait reset (§3) masih terpasang.

## 6. Data wajib unik per-run

Karena tidak ada fresh-migrate otomatis, semua email/nama fixture harus unik agar run berulang tidak gagal duplicate:

- Format: `donor-<time>-<Str::random(4)>@example.com` (readable di DB).
- Simpan di variabel, pakai variabel yang sama untuk factory, ketik form, login, dan `assertDatabaseHas`.
- `createAdmin()` jangan hardcode `admin@example.com` — pakai `firstOrCreate` atau email unik.
- Assertion yang mengasumsikan DB kosong dilarang:
  - TC-2-05: jangan `assertEquals(1, ...)` — pakai `assertDatabaseHas` email unik.
  - TC-3-02: `assertDontSee` harus pakai nama unik run, bukan nama generik.

Verifikasi: `php artisan dusk` hijau 2x berturut-turut tanpa reset manual.

## 7. Alur real via UI — berlaku umum + contoh

Prinsip umum: aksi yang diuji user harus lewat browser sungguhan, bukan factory/DB langsung. Factory hanya untuk menyiapkan data awal.

Pengecualian login: untuk aksi yang butuh login dulu (food posting, klaim, katalog, dsb) boleh pakai user existing yang sudah `aktif` di `foodbridge_dusk` — tidak wajib bikin user unik baru tiap run. Ambil dari phpMyAdmin (`users` → cari `role` sesuai + `status_akun = aktif`), lalu `loginAs($user)` atau login via form. Yang wajib unik per-run (§6) hanya test yang memang menguji pembuatan akun baru (register / verifikasi). Contoh pola yang benar:

```php
// Contoh: register + upload dokumen lewat form
$browser->visit('/register')
    ->type('@register-email', $email)   // $email unik per-run, lihat §6
    ->attach('dokumen', base_path('tests/Browser/fixtures/legalitas.pdf'))
    ->press('@register-submit')
    ->assertPathIs('/login');

// Contoh: login lalu verifikasi dashboard lewat UI
$browser->visit('/login')
    ->type('@login-email', $email)
    ->type('@login-password', 'password')
    ->press('@login-submit')
    ->assertPathIs('/dashboard/donor');

// Contoh: aksi admin lalu login ulang sebagai user terdampak
$browser->loginAs($admin)
    ->visit('/admin/verifications/'.$user->id)
    ->press('@verification-approve')
    ->logout()
    ->visit('/login')
    ->type('@login-email', $user->email)
    ->type('@login-password', 'password')
    ->press('@login-submit');
```

Catatan storage: upload Dusk masih ke `storage/app/` yang sama dengan dev (tidak dipisah per-env). Aman untuk DB, tapi folder bisa kotor — bersihkan file pengganti eksplisit (`Storage::delete()`) setelah assertion, jangan biarkan menumpuk.

## 8. Reset manual

DB akan membengkak antar run. Reset hanya manual saat dibutuhkan:

```sh
php artisan migrate:fresh --env=dusk.local
```

Dilarang menambahkan reset otomatis di dalam test.

## 9. Troubleshooting

| Gejala | Penyebab | Fix |
|---|---|---|
| `foodbridge_dusk` cuma ada `migrations` | `migrate --env=dusk.local` belum jalan, atau `DatabaseMigrations` masih terpasang | Lepas trait, jalankan §2 |
| Duplicate email di run ke-2 | Email statis | Terapkan §6 |
| `TC-3-02` flaky | `assertDontSee` nama generik ketemu data run lama | Pakai nama unik run |
| Data masuk `foodbridge` | Serve tanpa `--env=dusk.local` | Ulangi §4 |
| `php artisan test` gagal MySQL | `phpunit.xml` diubah | Kembalikan ke sqlite `:memory:` |

## 10. Cakupan file

- `tests/Browser/AuthenticationTest.php` — TC-2-01 s/d TC-2-09 (termasuk hak akses TC-2-07/08/09).
- `tests/Browser/AccountVerificationTest.php` — TC-3-01 s/d TC-3-07 + 04b.
- `tests/Browser/ProfileManagementTest.php` — TC-4-01 s/d TC-4-06.
