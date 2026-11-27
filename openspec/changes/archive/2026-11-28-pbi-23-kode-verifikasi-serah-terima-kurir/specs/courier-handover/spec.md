# Spec Delta

## Purpose

Menutup klaim kurir_online yang sudah approved menjadi Selesai setelah Donor memverifikasi kurir dan mengunggah foto bukti, lalu memberi tahu Penerima.

## ADDED Requirements

### Requirement: Donor pemilik melihat data pengemudi read-only

Sistem SHALL menampilkan platform, nama pengemudi, nomor kendaraan, kode verifikasi kurir, dan tangkapan layar pesanan pada rincian klaim approved kurir_online hanya kepada Donor pemilik postingan.

#### Scenario: Donor pemilik membuka rincian approved kurir_online

- **WHEN** Donor pemilik membuka Klaim Masuk detail klaim approved dengan courier_details tersimpan
- **THEN** data pengemudi tampil lengkap dan read-only

#### Scenario: Donor lain ditolak

- **WHEN** Donor bukan pemilik meminta rincian atau foto klaim milik Donor lain
- **THEN** sistem mengembalikan 403 dan tidak menampilkan data pengemudi maupun foto

#### Scenario: Penerima dan notifikasi tidak melihat foto serah terima

- **WHEN** Penerima membuka Klaim Saya atau notifikasi serah terima
- **THEN** status Selesai dan waktu tampil tanpa foto serah terima dan tanpa data sensitif tambahan

### Requirement: Tombol serah terima hanya saat layak selesai

Sistem SHALL menampilkan tombol "Makanan sudah diserahkan" hanya bila klaim approved, metode kurir_online, dan courier_details sudah tersimpan; selain itu tombol SHALL NOT tampil dan aksi langsung SHALL ditolak.

#### Scenario: Tombol tampil pada kondisi layak

- **WHEN** klaim approved kurir_online dengan courier_details dibuka Donor pemilik
- **THEN** tombol "Makanan sudah diserahkan" tampil bersama kolom unggah foto

#### Scenario: Metode atau data tidak memenuhi syarat

- **WHEN** Donor mengirim aksi selesai untuk klaim pending/rejected, metode ambil_sendiri, atau kurir_online tanpa courier_details
- **THEN** aksi ditolak, status tetap approved, completed_at tetap kosong, dan tidak ada notifikasi

### Requirement: Foto kurir wajib valid

Sistem SHALL mewajibkan foto_serah_terima berformat JPG, JPEG, PNG, atau WEBP dengan ukuran maksimal 2 MB pada aksi penyelesaian kurir.

#### Scenario: Foto kosong ditolak

- **WHEN** Donor menekan tombol tanpa memilih foto
- **THEN** sistem menampilkan "Foto kurir wajib diunggah.", status tetap approved, dan tidak ada data tersimpan

#### Scenario: Format tidak valid ditolak

- **WHEN** Donor mengunggah PDF atau EXE sebagai foto kurir
- **THEN** sistem menampilkan "Foto harus berformat JPG, JPEG, PNG, atau WEBP.", status tetap approved, dan tidak ada data tersimpan

#### Scenario: Ukuran melebihi batas ditolak

- **WHEN** Donor mengunggah foto 3 MB
- **THEN** sistem menampilkan "Ukuran foto maksimal 2 MB.", status tetap approved, dan tidak ada data tersimpan

### Requirement: Penyelesaian atomik menutup klaim dan memberi tahu Penerima

Sistem SHALL dalam satu transaksi menyimpan foto, mengisi courier_details.foto_serah_terima, mengubah status menjadi completed, mengisi completed_at, dan membuat notifikasi "Makanan Telah Diserahkan" kepada Penerima; bila bagian mana pun gagal, seluruh proses SHALL dibatalkan.

#### Scenario: Serah terima normal berhasil

- **WHEN** Donor A pada klaim approved kurir_online mengunggah kurir.jpg 1 MB dan menekan tombol
- **THEN** foto tersimpan, status completed, completed_at terisi, dan Penerima menerima notifikasi

#### Scenario: Penyimpanan gagal di-rollback

- **WHEN** penyimpanan foto atau perubahan status gagal di tengah proses
- **THEN** status tetap approved, foto_serah_terima dan completed_at kosong, dan tidak ada notifikasi

#### Scenario: Status dan waktu tampil di kedua sisi

- **WHEN** Donor dan Penerima membuka halaman klaim setelah selesai
- **THEN** keduanya melihat Selesai beserta waktu serah terima dan notifikasi mengarah ke Klaim Saya

### Requirement: Penyelesaian hanya berlaku satu kali

Sistem SHALL menolak aksi penyelesaian berulang pada klaim yang sudah completed tanpa mengubah status, completed_at, foto, dan tanpa notifikasi ganda; foto yang sudah tersimpan SHALL NOT dapat diubah.

#### Scenario: Klik ganda atau aksi ulang ditolak

- **WHEN** Donor mengirim ulang aksi penyelesaian pada klaim completed
- **THEN** aksi ditolak, data tidak berubah, dan tidak ada notifikasi ganda

#### Scenario: Foto tidak dapat diubah setelah selesai

- **WHEN** klaim sudah completed
- **THEN** tombol dan kolom unggah tidak tampil dan foto tidak dapat diganti
