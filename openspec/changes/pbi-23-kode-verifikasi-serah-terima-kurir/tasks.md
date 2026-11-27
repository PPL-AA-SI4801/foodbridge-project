# Tasks

## 1. Migrasi + Model

- [ ] 1.1 Buat migrasi `add_foto_serah_terima_to_courier_details_table` (string nullable) dan verifikasi dengan `php artisan migrate --pretend` + `migrate:status` menampilkan 18 migration.
- [ ] 1.2 Tambahkan `foto_serah_terima` ke fillable `CourierDetail` + accessor `fotoSerahTerimaUrl`, dan verifikasi dengan `php artisan tinker --execute` membuat/membaca atribut tanpa error.

## 2. Backend serah terima kurir

- [ ] 2.1 Tambahkan `ClaimHandoverService::completeCourier(Claim, UploadedFile, User)` (lock, cek approved + kurir_online + courierDetail ada, store `courier_handovers`, update completed/completed_at, notifikasi Penerima, cleanup file gagal) dan verifikasi dengan `php artisan test --filter=CourierHandoverTest::test_tc23_01` hijau.
- [ ] 2.2 Buat `CompleteCourierHandoverRequest` (authorize donor; rules foto required|image|mimes:jpg,jpeg,png,webp|max:2048 dengan pesan TC-23-03/04/05) dan verifikasi pesan muncul pada Feature test validasi.
- [ ] 2.3 Tambahkan `Donor\ClaimController@completeCourier` + route `PATCH donor/claims/{claim}/complete-courier` (nama `donor.claims.complete-courier`) dan verifikasi `php artisan route:list --name=donor.claims` memuat route baru tanpa mengubah `donor.claims.complete` PBI-12.

## 3. Tampilan Donor + Penerima + Admin/Notifikasi

- [ ] 3.1 Perluas modal `donor/claims/index.blade.php`: blok kurir approved menampilkan data driver read-only + form upload (`courier-handover-form-{id}`, `foto-serah-terima-{id}`, `complete-courier-{id}`) + blok selesai menampilkan badge "Diserahkan pada ..." + thumbnail foto; tombol hilang setelah completed; dan verifikasi Dusk TC-23-01/02 melihat elemen tersebut.
- [ ] 3.2 Tampilkan badge Selesai + waktu di `recipient/claims/index.blade.php` tanpa foto serah terima, pastikan notifikasi "Makanan Telah Diserahkan" mengarah ke `recipient.claims.index?claim=`; verifikasi via Dusk TC-23-02 (Penerima melihat waktu, lonceng mengarah ke Klaim Saya).
- [ ] 3.3 Pastikan halaman admin hanya membaca status/metode/waktu (tanpa foto/driver) dan verifikasi manual membuka `admin.claims.index` tidak menampilkan foto serah terima.

## 4. Feature test TC-23-01 s/d TC-23-10

- [ ] 4.1 Tulis `tests/Feature/Donor/CourierHandoverTest.php` (RefreshDatabase, sqlite) mencakup TC-23-01 normal, TC-23-02 badge waktu, TC-23-03 kosong, TC-23-04 format, TC-23-05 ukuran, TC-23-06 idempoten, TC-23-07 403 donor lain, TC-23-08 status bukan approved, TC-23-09 metode/data tidak layak, TC-23-10 rollback simulasi error; verifikasi dengan `php artisan test --filter=CourierHandoverTest` hijau.
- [ ] 4.2 Jalankan `php artisan test` penuh dan verifikasi tidak menyentuh MySQL (tetap sqlite `:memory:`) dan tidak merusak test PBI-12.

## 5. Dusk test + verifikasi standar TEST_RULE

- [ ] 5.1 Tulis `tests/Browser/CourierHandoverTest.php` tanpa trait reset DB, fixture unik `courier-<time>-<rand>@example.com`, pola login via form + `attach('foto_serah_terima', fixture)`, mencakup TC-23-01 s/d TC-23-07 via UI; verifikasi `grep -rn "DatabaseMigrations\|DatabaseTruncation\|RefreshDatabase" tests/Browser/` menghasilkan OK.
- [ ] 5.2 Verifikasi skema identik: `php artisan migrate --env=dusk.local`, `php artisan migrate:status --env=dusk.local` vs `php artisan migrate:status` sama (18 migration); serve `php artisan serve --env=dusk.local`, run `php artisan dusk --filter=CourierHandoverTest` hijau 2x berturut-turut tanpa reset; inspeksi `foodbridge_dusk` via phpMyAdmin/`SELECT` memastikan baris + `foto_serah_terima` tercatat dan `foodbridge` tidak bertambah.
- [ ] 5.3 Bersihkan file upload Dusk eksplisit (`Storage::disk('public')->delete()`) dan verifikasi tidak ada file yatim di `storage/app/public/courier_handovers` setelah run.
