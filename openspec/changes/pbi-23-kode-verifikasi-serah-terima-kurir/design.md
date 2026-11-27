# Design

## Context

PBI-12 sudah menyediakan pola serah terima mandiri: `ClaimHandoverService::complete()` + `CompleteClaimRequest` + route `donor.claims.complete` + modal di `donor/claims/index.blade.php`. PBI-11 menyimpan `courier_details` (platform, nama, nopol, kode kurir, foto_bukti screenshot) via `ClaimPickupService`. PBI-23 memakai ulang pola itu untuk jalur kurir: tidak ada input kode dari Donor (kode kurir hanya dicocokkan visual), gantinya wajib upload `foto_serah_terima`. Lihat `proposal.md` untuk motivasi.

Batasan: `courier_details.claim_id` unik; `foto_serah_terima` belum ada (butuh migrasi nullable); storage publik `courier_handovers`; visibilitas foto hanya Donor pemilik; notifikasi memakai `InAppNotification` tipe Claim seperti PBI-12.

## Goals / Non-Goals

**Goals:**

- Penyelesaian kurir atomik (foto + status + waktu + notifikasi) meniru transaksi PBI-12/PBI-11 dengan cleanup file gagal.
- Validasi foto di FormRequest + service (required, image mimes jpg/jpeg/png/webp, max 2048 KB) dengan pesan Indonesia sesuai TC-23-03 s/d TC-23-05.
- Otorisasi ganda: middleware role donor + cek `post.donor_id` di service (403 untuk Donor lain), guard 404 saat `post`/`recipient` hilang.

**Non-Goals:**

- Tidak mengubah alur PBI-12 (ambil_sendiri + kode 6 digit), PBI-09 (approve), PBI-11 (lapor driver).
- Tidak menambah kode verifikasi baru untuk kurir; tidak menampilkan foto ke Penerima/admin/notifikasi.
- Tidak memisahkan disk storage Dusk (mengikuti TEST_RULE: upload Dusk tetap ke `storage/app`, dibersihkan eksplisit).

## Decisions

1. **Kolom baru `courier_details.foto_serah_terima` nullable (bukan tabel baru).**
   Rasional: 1 klaim最多 1 kurir (unique claim_id); foto hanya ada setelah selesai. Alternatif tabel `handover_proofs` ditolak karena menambah join tanpa kebutuhan.
2. **Service baru `completeCourier()` di `ClaimHandoverService` (bukan reuse `complete()`).**
   Rasional: prasyarat dan input berbeda (kurir_online + courier_details + UploadedFile vs ambil_sendiri + kode). Satu transaksi `lockForUpdate` cek status approved → cek metode kurir_online → cek courier_details ada → store file → update + notifikasi. Alternatif controller gemuk ditolak agar Feature test bisa panggil service langsung seperti PBI-12.
3. **FormRequest baru `CompleteCourierHandoverRequest` + route `PATCH donor/claims/{claim}/complete-courier`.**
   Rasional: route terpisah mencegah tabrakan validasi kode PBI-12; pesan error spesifik foto. Alternatif satu route dengan branching ditolak karena mempersulit pesan dan Dusk selector.
4. **Blade: blok kurir di modal Donor berisi data driver + form upload + tombol; blok selesai berisi badge "Diserahkan pada ..." + thumbnail foto (hanya Donor).**
   Rasional: konsisten dengan blok PBI-12 di file yang sama; dusk attributes `courier-handover-form-{id}`, `foto-serah-terima-{id}`, `complete-courier-{id}`. Penerima hanya tambah badge waktu, tanpa foto.
5. **Notifikasi "Makanan Telah Diserahkan" ke `recipient.user_id` mengarah ke Klaim Saya (`recipient.claims.index?claim=`).**
   Rasional: sama seperti PBI-12 agar lonceng konsisten; foto tidak dimasukkan ke payload notifikasi.

## Risks / Trade-offs

- [File yatim bila DB gagal setelah store] → simpan path ke variabel, hapus di `catch` seperti `ClaimPickupService`, lalu rethrow.
- [Race klik ganda] → `lockForUpdate` + cek status approved di dalam transaksi; ulangan mendapat DomainException tanpa notifikasi ganda.
- [Dusk flaky upload] → pakai fixture kecil (<2 MB) di `tests/Browser/fixtures/` + `attach()`; bersihkan file via Storage setelah assertion; email unik per-run.
- [Foto lama menumpuk saat retry sebelum completed] → sebelum store baru, hapus `foto_serah_terima` lama bila ada (klaim masih approved).

## Migration Plan

1. Deploy migrasi `add_foto_serah_terima_to_courier_details` (nullable string); data lama tetap valid (kosong).
2. Deploy kode service/request/controller/view/route tanpa mengubah PBI-12.
3. Rollback: revert kode + `migrate:rollback` satu langkah; klaim completed tetap completed (tidak di-downgrade).

## Open Questions

- Tidak ada yang mengubah spec/approach/task; detail kecil (ukuran thumbnail, label badge) mengikuti design system saat implementasi.
