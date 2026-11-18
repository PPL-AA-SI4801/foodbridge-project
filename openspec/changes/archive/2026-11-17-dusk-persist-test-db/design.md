# Design

## Context

Lihat `proposal.md` untuk motivasi. Kondisi kini: `AuthenticationTest`, `AccountVerificationTest`, `ProfileManagementTest` memakai `DatabaseMigrations` (`migrate:fresh` + `migrate:rollback` di `beforeApplicationDestroyed`), sehingga `foodbridge_dusk` tersisa tabel `migrations` saja. Lihat `specs/dusk-testing/spec.md` untuk kontrak perilaku. DB: asli `foodbridge` (`.env.example`), Dusk `foodbridge_dusk` (`.env.dusk.local`), Feature/Unit `sqlite :memory:` (`phpunit.xml`) — tidak diubah.

## Goals / Non-Goals

**Goals:**
- Data + skema Dusk menetap dan dapat diinspeksi di `foodbridge_dusk`.
- Run berulang hijau tanpa reset otomatis.
- Satu dokumen acuan (`docs/TEST_RULE.md`) + `CONTRIBUTING.md` konsisten.

**Non-Goals:**
- Tidak mengubah app code (`app/`, `routes/`, `config/database.php`), skema migration, atau driver DB.
- Tidak menyentuh `AccountBlockingTest.php` dan `phpunit.xml`.
- Tidak memisahkan `storage/app/` per-env pada change ini (tetap berbagi, cleanup selektif saja).

## Decisions

1. **Hapus `DatabaseMigrations`, ganti dengan migrate manual sekali.**
   Rationale: satu-satunya cara menghentikan rollback otomatis. Alternatif `DatabaseTruncation` ditolak karena tetap menghapus data (hanya struktur dipertahankan).
2. **Unique fixture per-run (`time()` + `Str::random(4)`) di helper + form input.**
   Rationale: pengganti garansi isolasi yang dulu diberikan fresh-migrate. Alternatif UUID penuh ditolak agar email tetap readable di DB (`donor-173556...-aB3c@example.com`). `createAdmin()` jadi `firstOrCreate`/unik agar tidak duplicate `admin@example.com`.
3. **Perbaiki 2 assertion rapuh:** TC-2-05 (`assertEquals(1)` → `assertDatabaseHas` email unik), TC-3-02 (`assertDontSee` nama generik → nama unik run).
   Rationale: keduanya mengasumsikan DB kosong. Alternatif reset otomatis tiap run ditolak karena melanggar tujuan persist.
4. **`docs/TEST_RULE.md` sebagai source of truth perintah** (isi di bawah), `CONTRIBUTING.md` hanya menunjuk ke sana untuk bagian DB-test.
   Rationale: menghindari duplikasi aturan yang mudah drift. Draft isi `TEST_RULE.md`: DB mana untuk apa, `CREATE DATABASE`, `migrate --env=dusk.local`, `serve --env=dusk.local`, `php artisan dusk [--filter]`, `SELECT` inspeksi, `migrate:fresh --env=dusk.local` reset manual, larangan pointing ke `foodbridge`/prod, kewajiban unique data, catatan `storage/app/` berbagi + cleanup `Storage::delete()` ala TC-3-04b, `php artisan test` tetap sqlite memory.

## Risks / Trade-offs

- [DB membengkak] → Mitigasi: perintah reset manual terdokumentasi; boleh `migrate:fresh --env=dusk.local` berkala.
- [Nama unik membuat `assertDontSee`/`assertSee` salah sasaran jika salah variabel] → Mitigasi: simpan email unik di variabel, pakai variabel yang sama untuk type, login, dan assert.
- [Upload fixture mengotori `storage/app/`] → Mitigasi: pertahankan cleanup eksplisit setelah assert; tidak blocking.
- [Kontributor lupa `--env=dusk.local` dan menulis ke `foodbridge`] → Mitigasi: `TEST_RULE.md` + pre-run check `DB_DATABASE` di tasks;que queue/cache/session `database` ikut ke `foodbridge_dusk` — aman karena terisolasi.

## Migration Plan

1. Apply tasks (hapus trait → unique helper → perbaiki 2 assertion → tulis `docs/TEST_RULE.md` → revisi `CONTRIBUTING.md`).
2. Sekali: `CREATE DATABASE foodbridge_dusk`, `migrate --env=dusk.local`.
3. Verifikasi: `migrate:status` kedua env sama, `dusk` hijau 2x tanpa reset, `SELECT` terlihat.
4. Rollback: `migrate:fresh --env=dusk.local`; jika ingin kembali ke perilaku lama, pasang ulang `DatabaseMigrations` (tercatat di tasks sebagai catatan, bukan default).
