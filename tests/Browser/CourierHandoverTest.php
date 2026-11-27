<?php

namespace Tests\Browser;

use App\Enums\FoodPostStatus;
use App\Enums\Role;
use App\Models\Claim;
use App\Models\CourierDetail;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\InAppNotification;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class CourierHandoverTest extends DuskTestCase
{
    private const REVIEW_PAUSE = 1500;

    public function test_tc23_01_donor_melihat_data_pengemudi_lalu_menyerahkan_makanan(): void
    {
        [$donorUser, , $claim, $driver] = $this->createCourierHandoverData();
        $photo = base_path('tests/Browser/fixtures/courier_proof.png');

        $this->browse(function (Browser $browser) use ($donorUser, $claim, $driver, $photo) {
            $browser->logout()->visit('/login')
                ->type('@login-email', $donorUser->email)
                ->type('@login-password', 'password')
                ->press('@login-submit')
                ->waitForLocation('/dashboard/donor')
                ->visit('/donor/claims?claim='.$claim->id)
                ->click('@donor-claim-detail-'.$claim->id)
                ->waitFor('@courier-handover-form-'.$claim->id)
                // Data pengemudi tampil read-only sebelum serah terima.
                ->assertSee($driver['nama'])
                ->assertSee($driver['nopol'])
                ->assertSee($driver['kode'])
                ->attach('@foto-serah-terima-'.$claim->id, $photo)
                ->pause(self::REVIEW_PAUSE)
                ->press('@complete-courier-'.$claim->id)
                ->waitForText('Makanan sudah diserahkan')
                ->pause(self::REVIEW_PAUSE);
        });

        $this->assertSame('completed', Claim::find($claim->id)->status);
        $this->assertNotNull(Claim::find($claim->id)->completed_at);
        $this->assertNotNull(Claim::find($claim->id)->courierDetail->foto_serah_terima);

        $this->deleteHandoverPhoto($claim->id);
    }

    public function test_tc23_02_status_selesai_tampil_donor_penerima_dan_notifikasi(): void
    {
        [$donorUser, $recipientUser, $claim] = $this->createCourierHandoverData();
        $photo = base_path('tests/Browser/fixtures/courier_proof.png');

        $this->browse(function (Browser $browser) use ($donorUser, $recipientUser, $claim, $photo) {
            // Donor menyelesaikan serah terima.
            $browser->logout()->visit('/login')
                ->type('@login-email', $donorUser->email)
                ->type('@login-password', 'password')
                ->press('@login-submit')
                ->waitForLocation('/dashboard/donor')
                ->visit('/donor/claims?claim='.$claim->id)
                ->click('@donor-claim-detail-'.$claim->id)
                ->waitFor('@courier-handover-form-'.$claim->id)
                ->attach('@foto-serah-terima-'.$claim->id, $photo)
                ->press('@complete-courier-'.$claim->id)
                ->waitForText('Makanan sudah diserahkan')
                ->pause(self::REVIEW_PAUSE);

            // Donor melihat badge Selesai + waktu, form tidak tampil lagi.
            $browser->visit('/donor/claims?claim='.$claim->id)
                ->assertMissing('@courier-handover-form-'.$claim->id)
                ->assertSee('Diserahkan');

            // Penerima melihat status Selesai beserta waktu serah terima.
            $browser->visit('/donor/claims')
                ->click('@logout')
                ->waitForLocation('/login')
                ->logout()->visit('/login')
                ->type('@login-email', $recipientUser->email)
                ->type('@login-password', 'password')
                ->press('@login-submit')
                ->waitForLocation('/dashboard/penerima')
                ->visit('/recipient/claims?claim='.$claim->id)
                ->assertSee('Selesai')
                ->pause(self::REVIEW_PAUSE);
        });

        $notification = InAppNotification::query()
            ->where('user_id', $recipientUser->id)
            ->where('judul', 'Makanan Telah Diserahkan')
            ->first();

        $this->assertNotNull($notification);

        // Notifikasi mengarah ke Klaim Saya.
        $this->browse(function (Browser $browser) use ($recipientUser, $notification) {
            $browser->logout()->visit('/login')
                ->type('@login-email', $recipientUser->email)
                ->type('@login-password', 'password')
                ->press('@login-submit')
                ->waitForLocation('/dashboard/penerima')
                ->visit('/notifikasi/'.$notification->id)
                ->waitForLocation('/recipient/claims')
                ->pause(self::REVIEW_PAUSE);
        });

        $this->deleteHandoverPhoto($claim->id);
    }

    public function test_tc23_03_foto_kosong_ditolak(): void
    {
        [$donorUser, , $claim] = $this->createCourierHandoverData();

        $this->browse(function (Browser $browser) use ($donorUser, $claim) {
            $browser->logout()->visit('/login')
                ->type('@login-email', $donorUser->email)
                ->type('@login-password', 'password')
                ->press('@login-submit')
                ->waitForLocation('/dashboard/donor')
                ->visit('/donor/claims?claim='.$claim->id)
                ->click('@donor-claim-detail-'.$claim->id)
                ->waitFor('@courier-handover-form-'.$claim->id)
                ->press('@complete-courier-'.$claim->id)
                ->pause(2000)
                ->click('@donor-claim-detail-'.$claim->id)
                ->waitFor('@courier-handover-errors-'.$claim->id)
                ->assertSee('Foto kurir wajib diunggah.')
                ->pause(self::REVIEW_PAUSE);
        });

        $this->assertSame('approved', Claim::find($claim->id)->status);
        $this->assertNull(Claim::find($claim->id)->courierDetail->foto_serah_terima);
    }

    public function test_tc23_04_format_foto_tidak_valid_ditolak(): void
    {
        [$donorUser, , $claim] = $this->createCourierHandoverData();
        $pdf = base_path('tests/Browser/fixtures/legalitas.pdf');

        $this->browse(function (Browser $browser) use ($donorUser, $claim, $pdf) {
            $browser->logout()->visit('/login')
                ->type('@login-email', $donorUser->email)
                ->type('@login-password', 'password')
                ->press('@login-submit')
                ->waitForLocation('/dashboard/donor')
                ->visit('/donor/claims?claim='.$claim->id)
                ->click('@donor-claim-detail-'.$claim->id)
                ->waitFor('@courier-handover-form-'.$claim->id)
                ->attach('@foto-serah-terima-'.$claim->id, $pdf)
                ->press('@complete-courier-'.$claim->id)
                ->pause(2000)
                ->click('@donor-claim-detail-'.$claim->id)
                ->waitFor('@courier-handover-errors-'.$claim->id)
                ->assertSee('Foto harus berformat JPG, JPEG, PNG, atau WEBP.')
                ->pause(self::REVIEW_PAUSE);
        });

        $this->assertSame('approved', Claim::find($claim->id)->status);
        $this->assertNull(Claim::find($claim->id)->courierDetail->foto_serah_terima);
    }

    public function test_tc23_05_ukuran_foto_melebihi_batas_ditolak(): void
    {
        [$donorUser, , $claim] = $this->createCourierHandoverData();
        $oversize = $this->oversizedPhoto();

        try {
            $this->browse(function (Browser $browser) use ($donorUser, $claim, $oversize) {
                $browser->logout()->visit('/login')
                    ->type('@login-email', $donorUser->email)
                    ->type('@login-password', 'password')
                    ->press('@login-submit')
                    ->waitForLocation('/dashboard/donor')
                    ->visit('/donor/claims?claim='.$claim->id)
                    ->click('@donor-claim-detail-'.$claim->id)
                    ->waitFor('@courier-handover-form-'.$claim->id)
                    ->attach('@foto-serah-terima-'.$claim->id, $oversize)
                    ->press('@complete-courier-'.$claim->id)
                    ->pause(2000)
                    ->click('@donor-claim-detail-'.$claim->id)
                    ->waitFor('@courier-handover-errors-'.$claim->id)
                    ->assertSee('Ukuran foto maksimal 2 MB.')
                    ->pause(self::REVIEW_PAUSE);
            });
        } finally {
            @unlink($oversize);
        }

        $this->assertSame('approved', Claim::find($claim->id)->status);
        $this->assertNull(Claim::find($claim->id)->courierDetail->foto_serah_terima);
    }

    public function test_tc23_06_penyelesaian_berulang_ditolak_tanpa_notifikasi_ganda(): void
    {
        [$donorUser, $recipientUser, $claim] = $this->createCourierHandoverData();
        $photo = base_path('tests/Browser/fixtures/courier_proof.png');

        $this->browse(function (Browser $browser) use ($donorUser, $claim, $photo) {
            $browser->logout()->visit('/login')
                ->type('@login-email', $donorUser->email)
                ->type('@login-password', 'password')
                ->press('@login-submit')
                ->waitForLocation('/dashboard/donor')
                ->visit('/donor/claims?claim='.$claim->id)
                ->click('@donor-claim-detail-'.$claim->id)
                ->waitFor('@courier-handover-form-'.$claim->id)
                ->attach('@foto-serah-terima-'.$claim->id, $photo)
                ->press('@complete-courier-'.$claim->id)
                ->waitForText('Makanan sudah diserahkan')
                ->pause(self::REVIEW_PAUSE);

            // Form tidak tampil lagi pada klaim yang selesai.
            $browser->visit('/donor/claims?claim='.$claim->id)
                ->assertMissing('@courier-handover-form-'.$claim->id)
                ->assertSee('Diserahkan');

            // Aksi ulang tanpa foto tetap ditolak tanpa mengubah data.
            $browser->driver->executeScript(
                "const t=document.querySelector('input[name=\"_token\"]').value;"
                ."const f=document.createElement('form');f.method='POST';"
                ."f.action='/donor/claims/{$claim->id}/complete-courier';"
                ."f.innerHTML='<input type=\"hidden\" name=\"_token\" value=\"'+t+'\"><input type=\"hidden\" name=\"_method\" value=\"PATCH\">';"
                .'document.body.appendChild(f);f.submit();'
            );
            // POST ulang tanpa foto ditolak (redirect validasi) dan tidak mengubah data.
            $browser->waitForText('Diserahkan')
                ->pause(self::REVIEW_PAUSE);
        });

        $this->assertSame('completed', Claim::find($claim->id)->status);
        $this->assertNotNull(Claim::find($claim->id)->completed_at);
        $this->assertNotNull(Claim::find($claim->id)->courierDetail->foto_serah_terima);
        $this->assertSame(1, InAppNotification::query()
            ->where('user_id', $recipientUser->id)
            ->where('judul', 'Makanan Telah Diserahkan')
            ->count());

        $this->deleteHandoverPhoto($claim->id);
    }

    public function test_tc23_07_donor_lain_tidak_melihat_data_pengemudi(): void
    {
        [$donorUser, , $claim, $driver] = $this->createCourierHandoverData();

        $otherDonorUser = User::factory()->create([
            'email' => 'courier-donor-lain-'.Str::random(8).'@example.com',
            'role' => Role::Donor,
            'status_akun' => 'aktif',
        ]);
        $otherDonorUser->donorProfile()->create([
            'nama_usaha' => 'Toko Lain '.Str::random(6),
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Lain No. 9, Bandung',
            'lat' => -6.3,
            'lng' => 106.9,
        ]);

        $this->browse(function (Browser $browser) use ($otherDonorUser, $claim, $driver) {
            $browser->logout()->visit('/login')
                ->type('@login-email', $otherDonorUser->email)
                ->type('@login-password', 'password')
                ->press('@login-submit')
                ->waitForLocation('/dashboard/donor')
                ->visit('/donor/claims')
                ->assertDontSee($driver['nama'])
                ->visit('/donor/claims?claim='.$claim->id)
                ->assertDontSee($driver['nama'])
                ->assertDontSee($driver['kode'])
                ->pause(self::REVIEW_PAUSE);
        });

        $this->assertSame('approved', Claim::find($claim->id)->status);
        $this->assertSame(0, InAppNotification::query()
            ->where('judul', 'Makanan Telah Diserahkan')
            ->whereIn('user_id', [$otherDonorUser->id, $donorUser->id])
            ->count());
    }

    /**
     * Foto >2 MB tanpa menambah blob ke repo: fixture kecil + padding.
     */
    private function oversizedPhoto(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'courier-oversize-').'.png';
        $base = file_get_contents(base_path('tests/Browser/fixtures/courier_proof.png'));
        file_put_contents($path, $base.str_repeat("\0", 3 * 1024 * 1024));

        return $path;
    }

    private function deleteHandoverPhoto(int $claimId): void
    {
        $path = Claim::find($claimId)?->courierDetail?->foto_serah_terima;

        if (is_string($path) && $path !== '') {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @return array{0: User, 1: User, 2: Claim, 3: array{nama: string, nopol: string, kode: string}}
     */
    private function createCourierHandoverData(): array
    {
        $suffix = Str::random(8);

        $donorUser = User::factory()->create([
            'email' => "courier-donor-{$suffix}@example.com",
            'role' => Role::Donor,
            'status_akun' => 'aktif',
        ]);
        $donorProfile = $donorUser->donorProfile()->create([
            'nama_usaha' => "Toko Kurir {$suffix}",
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor No. 1, Bandung',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        $recipientUser = User::factory()->create([
            'email' => "courier-penerima-{$suffix}@example.com",
            'role' => Role::Penerima,
            'status_akun' => 'aktif',
        ]);
        $recipientProfile = $recipientUser->recipientProfile()->create([
            'nama_lembaga' => "Panti Kurir {$suffix}",
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => 'Jl. Penerima No. 2, Bandung',
            'lat' => -6.9,
            'lng' => 107.6,
        ]);

        $category = FoodCategory::create(['nama_kategori' => "Kurir {$suffix}"]);
        $post = FoodPost::create([
            'judul' => "Nasi Box Kurir {$suffix}",
            'deskripsi' => 'Nasi box untuk uji serah terima kurir.',
            'foto' => 'seed/placeholder.webp',
            'jumlah_porsi' => 10,
            'sisa_porsi' => 10,
            'alamat' => 'Jl. Donor No. 1, Bandung',
            'lat' => -6.2,
            'lng' => 106.8,
            'batas_konsumsi' => now()->addDay(),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donorProfile->id,
            'category_id' => $category->id,
        ]);
        $claim = Claim::create([
            'post_id' => $post->id,
            'recipient_id' => $recipientProfile->id,
            'jumlah_porsi' => 2,
            'status' => 'approved',
            'metode_pengambilan' => Claim::METHOD_KURIR_ONLINE,
            'tanggal_klaim' => today(),
            'approved_at' => now(),
        ]);

        $driver = [
            'nama' => 'Budi Santoso '.$suffix,
            'nopol' => 'D 1234 '.$suffix,
            'kode' => 'GJ-'.$suffix,
        ];

        CourierDetail::create([
            'claim_id' => $claim->id,
            'platform' => 'Gojek',
            'nama_pengemudi' => $driver['nama'],
            'no_kendaraan' => $driver['nopol'],
            'kode_verifikasi_kurir' => $driver['kode'],
            'foto_bukti' => 'courier_proofs/bukti.png',
            'created_at' => now(),
        ]);

        return [$donorUser, $recipientUser, $claim, $driver];
    }
}
