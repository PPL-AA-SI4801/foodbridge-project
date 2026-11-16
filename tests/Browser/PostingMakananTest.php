<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PostingMakananTest extends DuskTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mengisi koordinat akun donor untuk kebutuhan pengujian.
        $donor = User::where('email', 'donor@foodbridge.test')
            ->firstOrFail();

        $donor->donorProfile()->update([
            'lat' => 5.5483,
            'lng' => 95.3238,
        ]);
    }

    /**
     * Menguji apakah donor dapat membuka halaman posting makanan.
     */
    public function test_donor_dapat_membuka_halaman_posting(): void
    {
        $donor = User::where('email', 'donor@foodbridge.test')
            ->firstOrFail();

        $this->browse(function (Browser $browser) use ($donor) {
            $browser->loginAs($donor)
                ->visit('/donor/food-posts/create')
                ->assertPresent('#judul')
                ->assertPresent('#deskripsi')
                ->assertPresent('#foto')
                ->assertPresent('#jumlah_porsi')
                ->assertPresent('#batas_konsumsi')
                ->assertPresent('#alamat')
                ->assertPresent('#lat')
                ->assertPresent('#lng');
        });
    }

    /**
     * Menguji donor berhasil membuat postingan makanan.
     */
    public function test_donor_dapat_membuat_postingan_makanan(): void
    {
        $donor = User::where('email', 'donor@foodbridge.test')
            ->firstOrFail();

        $this->browse(function (Browser $browser) use ($donor) {
            $browser->loginAs($donor)
                ->visit('/donor/food-posts/create')
                ->type('judul', 'Nasi Kotak Uji Dusk')
                ->type('deskripsi', 'Nasi kotak untuk pengujian aplikasi.')
                ->click('label:has(input[name="category_id"][value="1"])')
                ->type('jumlah_porsi', '20')
                ->type(
                    'batas_konsumsi',
                    now()->addDays(2)->format('Y-m-d\TH:i')
                )
                ->type('alamat', 'Jl. Demo No. 1, Jakarta')
                ->attach(
                    'foto',
                    base_path('tests/Fixtures/makanan.jpg')
                )
                ->press('Simpan')
                ->pause(1500);

                dump($browser->script('return document.body.innerText')[0]);
        });
    }

}
