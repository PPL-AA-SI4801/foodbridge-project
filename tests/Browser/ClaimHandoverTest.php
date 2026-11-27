<?php

namespace Tests\Browser;

use App\Enums\FoodPostStatus;
use App\Enums\Role;
use App\Models\Claim;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\InAppNotification;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;


class ClaimHandoverTest extends DuskTestCase
{
    private const REVIEW_PAUSE = 1500;

    public function test_tc12_01_kode_dibuat_dan_hanya_terlihat_penerima(): void
    {
        [$donorUser, $recipientUser, $claim] = $this->createHandoverData();
        $code = null;

        $this->browse(function (Browser $browser) use ($donorUser, $recipientUser, $claim, &$code) {
            // Penerima membuka Klaim Saya, fallback menerbitkan kode.
            $browser->logout()->visit('/login')
                ->type('@login-email', $recipientUser->email)
                ->type('@login-password', 'password')
                ->press('@login-submit')
                ->waitForLocation('/dashboard/penerima')
                ->visit('/recipient/claims?claim='.$claim->id)
                // Modal detail terbuka otomatis via ?claim=.
                ->waitFor('@verification-code-'.$claim->id)
                ->pause(self::REVIEW_PAUSE);

            $code = Claim::find($claim->id)->kode_verifikasi;
            $this->assertMatchesRegularExpression('/^[0-9]{6}$/', (string) $code);
            $browser->assertSee($code);

            // Donor membuka detail klaim: kode tidak tampil, hanya kolom input.
            $browser->visit('/recipient/claims')
                ->click('@logout')
                ->waitForLocation('/login')
                ->logout()->visit('/login')
                ->type('@login-email', $donorUser->email)
                ->type('@login-password', 'password')
                ->press('@login-submit')
                ->waitForLocation('/dashboard/donor')
                ->visit('/donor/claims?claim='.$claim->id)
                ->click('@donor-claim-detail-'.$claim->id)
                ->waitFor('@complete-claim-form-'.$claim->id)
                ->assertDontSee($code)
                ->pause(self::REVIEW_PAUSE);
        });
    }

    public function test_tc12_02_donor_menyelesaikan_serah_terima_dengan_kode_benar(): void
    {
        [$donorUser, , $claim] = $this->createHandoverData($this->randomCode());
        $code = Claim::find($claim->id)->kode_verifikasi;

        $this->browse(function (Browser $browser) use ($donorUser, $claim, $code) {
            $browser->logout()->visit('/login')
                ->type('@login-email', $donorUser->email)
                ->type('@login-password', 'password')
                ->press('@login-submit')
                ->waitForLocation('/dashboard/donor')
                ->visit('/donor/claims?claim='.$claim->id)
                ->click('@donor-claim-detail-'.$claim->id)
                ->waitFor('@complete-claim-form-'.$claim->id)
                ->assertDontSee($code)
                ->type('@kode-verifikasi-'.$claim->id, $code)
                ->pause(self::REVIEW_PAUSE)
                ->press('@complete-claim-'.$claim->id)
                ->waitForText('Makanan sudah diserahkan')
                ->pause(self::REVIEW_PAUSE);
        });

        $this->assertSame('completed', Claim::find($claim->id)->status);
        $this->assertNotNull(Claim::find($claim->id)->completed_at);
        $this->assertSame(
            'Makanan Telah Diserahkan',
            InAppNotification::query()->where('user_id', $claim->fresh()->recipient->user_id)
                ->latest('id')->first()?->judul
        );
    }

    public function test_tc12_03_kode_salah_atau_kosong_ditolak_lalu_kode_benar_diterima(): void
    {
        [$donorUser, , $claim] = $this->createHandoverData($this->randomCode());
        $code = Claim::find($claim->id)->kode_verifikasi;

        $this->browse(function (Browser $browser) use ($donorUser, $claim, $code) {
            $browser->logout()->visit('/login')
                ->type('@login-email', $donorUser->email)
                ->type('@login-password', 'password')
                ->press('@login-submit')
                ->waitForLocation('/dashboard/donor')
                ->visit('/donor/claims?claim='.$claim->id);

            // Kode salah: ditolak, status tetap approved.
            $browser->click('@donor-claim-detail-'.$claim->id)
                ->waitFor('@complete-claim-form-'.$claim->id)
                ->type('@kode-verifikasi-'.$claim->id, '000000')
                ->press('@complete-claim-'.$claim->id)
                ->waitForText('Kode verifikasi tidak sesuai')
                ->pause(self::REVIEW_PAUSE);

            $this->assertSame('approved', Claim::find($claim->id)->status);
            $this->assertSame($code, Claim::find($claim->id)->kode_verifikasi);

            // Kode kosong: ditolak validasi dengan pesan khusus.
            $browser->visit('/donor/claims?claim='.$claim->id)
                ->click('@donor-claim-detail-'.$claim->id)
                ->waitFor('@complete-claim-form-'.$claim->id)
                ->press('@complete-claim-'.$claim->id)
                ->pause(2000)
                ->click('@donor-claim-detail-'.$claim->id)
                ->waitFor('@complete-claim-errors-'.$claim->id)
                ->assertSee('Kode verifikasi wajib diisi')
                ->pause(self::REVIEW_PAUSE);

            // Kode benar: diterima, klaim selesai.
            $browser->visit('/donor/claims?claim='.$claim->id)
                ->click('@donor-claim-detail-'.$claim->id)
                ->waitFor('@complete-claim-form-'.$claim->id)
                ->type('@kode-verifikasi-'.$claim->id, $code)
                ->press('@complete-claim-'.$claim->id)
                ->waitForText('Makanan sudah diserahkan')
                ->pause(self::REVIEW_PAUSE);
        });

        $this->assertSame('completed', Claim::find($claim->id)->status);
    }

    public function test_tc12_04_penyelesaian_berulang_ditolak_tanpa_notifikasi_ganda(): void
    {
        [$donorUser, , $claim] = $this->createHandoverData($this->randomCode());
        $code = Claim::find($claim->id)->kode_verifikasi;

        $this->browse(function (Browser $browser) use ($donorUser, $claim, $code) {
            $browser->logout()->visit('/login')
                ->type('@login-email', $donorUser->email)
                ->type('@login-password', 'password')
                ->press('@login-submit')
                ->waitForLocation('/dashboard/donor')
                ->visit('/donor/claims?claim='.$claim->id)
                ->click('@donor-claim-detail-'.$claim->id)
                ->waitFor('@complete-claim-form-'.$claim->id)
                ->type('@kode-verifikasi-'.$claim->id, $code)
                ->press('@complete-claim-'.$claim->id)
                ->waitForText('Makanan sudah diserahkan')
                ->pause(self::REVIEW_PAUSE);

            // Form penyelesaian tidak lagi tampil pada klaim yang selesai.
            $browser->visit('/donor/claims?claim='.$claim->id)
                ->assertMissing('@complete-claim-form-'.$claim->id)
                ->assertSee('Diserahkan');

            // Permintaan ulang nyata lewat browser (sesi donor pemilik).
            $browser->driver->executeScript(
                "const t=document.querySelector('input[name=\"_token\"]').value;"
                ."const f=document.createElement('form');f.method='POST';"
                ."f.action='/donor/claims/{$claim->id}/complete';"
                ."f.innerHTML='<input type=\"hidden\" name=\"_token\" value=\"'+t+'\">"
                .'<input type="hidden" name="_method" value="PATCH">'
                ."<input type=\"hidden\" name=\"kode_verifikasi\" value=\"{$code}\">';"
                .'document.body.appendChild(f);f.submit();'
            );
            $browser->waitForText('Hanya klaim yang disetujui yang dapat diselesaikan')
                ->pause(self::REVIEW_PAUSE);
        });

        $this->assertSame(1, InAppNotification::query()
            ->where('user_id', $claim->fresh()->recipient->user_id)
            ->where('judul', 'Makanan Telah Diserahkan')
            ->count());
    }

    public function test_tc12_05_donor_lain_tidak_dapat_menyelesaikan_klaim(): void
    {
        [$donorUser, , $claim] = $this->createHandoverData($this->randomCode());

        $otherDonorUser = User::factory()->create([
            'email' => 'handover-donor-lain-'.Str::random(8).'@example.com',
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

        $this->browse(function (Browser $browser) use ($otherDonorUser, $claim) {
            $browser->logout()->visit('/login')
                ->type('@login-email', $otherDonorUser->email)
                ->type('@login-password', 'password')
                ->press('@login-submit')
                ->waitForLocation('/dashboard/donor')
                ->visit('/donor/claims');

            // Donor lain mengirim aksi penyelesaian milik donor A lewat browser.
            $browser->driver->executeScript(
                "const t=document.querySelector('input[name=\"_token\"]')?.value ?? '';"
                ."const f=document.createElement('form');f.method='POST';"
                ."f.action='/donor/claims/{$claim->id}/complete';"
                ."f.innerHTML='<input type=\"hidden\" name=\"_token\" value=\"'+t+'\">"
                .'<input type="hidden" name="_method" value="PATCH">'
                ."<input type=\"hidden\" name=\"kode_verifikasi\" value=\"000000\">';"
                .'document.body.appendChild(f);f.submit();'
            );
            $browser->waitForText('403')
                ->pause(self::REVIEW_PAUSE);
        });

        $this->assertSame('approved', Claim::find($claim->id)->status);
        $this->assertSame(0, InAppNotification::query()
            ->where('user_id', $claim->fresh()->recipient->user_id)
            ->where('judul', 'Makanan Telah Diserahkan')
            ->count());
    }

    private function randomCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * @return array{0: User, 1: User, 2: Claim}
     */
    private function createHandoverData(?string $code = null): array
    {
        $suffix = Str::random(8);

        $donorUser = User::factory()->create([
            'email' => "handover-donor-{$suffix}@example.com",
            'role' => Role::Donor,
            'status_akun' => 'aktif',
        ]);
        $donorProfile = $donorUser->donorProfile()->create([
            'nama_usaha' => "Toko Handover {$suffix}",
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor No. 1, Bandung',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        $recipientUser = User::factory()->create([
            'email' => "handover-penerima-{$suffix}@example.com",
            'role' => Role::Penerima,
            'status_akun' => 'aktif',
        ]);
        $recipientProfile = $recipientUser->recipientProfile()->create([
            'nama_lembaga' => "Panti Handover {$suffix}",
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => 'Jl. Penerima No. 2, Bandung',
            'lat' => -6.9,
            'lng' => 107.6,
        ]);

        $category = FoodCategory::create(['nama_kategori' => "Handover {$suffix}"]);
        $post = FoodPost::create([
            'judul' => "Nasi Box Handover {$suffix}",
            'deskripsi' => 'Nasi box untuk uji serah terima.',
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
            'metode_pengambilan' => Claim::METHOD_AMBIL_SENDIRI,
            'kode_verifikasi' => $code,
            'tanggal_klaim' => today(),
            'approved_at' => now(),
        ]);

        return [$donorUser, $recipientUser, $claim];
    }
}
