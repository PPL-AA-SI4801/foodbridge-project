<?php

namespace Tests\Browser;

use App\Enums\FoodPostStatus;
use App\Enums\Role;
use App\Models\Claim;
use App\Models\DonorProfile;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\RecipientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Storage;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PostingMakananTest extends DuskTestCase
{
    use DatabaseTruncation;

    public function test_donor_dapat_membuka_halaman_posting_makanan(): void
    {
        [$donorUser] = $this->createDonor();
        $category = FoodCategory::create(['nama_kategori' => 'Karbohidrat']);

        $this->browse(function (Browser $browser) use ($donorUser) {
            $browser->loginAs($donorUser)
                ->visit('/donor/food-posts/create')
                ->assertPathIs('/donor/food-posts/create')
                ->assertSee('Posting Surplus Makanan')
                ->assertPresent('#judul')
                ->assertPresent('#deskripsi')
                ->assertPresent('#foto')
                ->assertPresent('#jumlah_porsi')
                ->assertPresent('#batas_konsumsi')
                ->assertPresent('#alamat')
                ->assertPresent('#lat')
                ->assertPresent('#lng')
                ->assertPresent('input[name="category_id"]');
        });
    }

    public function test_donor_berhasil_membuat_postingan_makanan(): void
    {
        [$donorUser, $donorProfile] = $this->createDonor();
        $category = FoodCategory::create(['nama_kategori' => 'Protein']);
        $title = 'Nasi Kotak Uji Dusk '.uniqid();
        $sampleImage = $this->createTempJpeg();

        try {
            $this->browse(function (Browser $browser) use ($donorUser, $category, $sampleImage, $title) {
                $browser->loginAs($donorUser)
                    ->visit('/donor/food-posts/create')
                    ->type('judul', $title)
                    ->type('deskripsi', 'Nasi kotak untuk pengujian Dusk.')
                    ->clear('jumlah_porsi')
                    ->type('jumlah_porsi', '20');

            $deadline = now()->addDays(2)->format('Y-m-d\TH:i');
            $browser->script('document.getElementById("batas_konsumsi").value = "'.$deadline.'"; document.getElementById("batas_konsumsi").dispatchEvent(new Event("change", {bubbles:true}));');

            $browser->type('alamat', 'Jl. Uji No. 1, Jakarta')
                    ->attach('foto', $sampleImage);

            $browser->script("document.querySelector('input[name=\"category_id\"][value=\"".$category->id."\"]').checked = true;");
            $browser->press('Terbitkan Postingan Sekarang');
            $browser->pause(2000);
            $browser->waitForText('Postingan makanan berhasil dibuat.')
                    ->assertSee($title);
            });
        } finally {
            @unlink($sampleImage);
        }

        $foodPost = FoodPost::where('judul', $title)->firstOrFail();
        $this->assertSame($donorProfile->id, $foodPost->donor_id);
        $this->assertSame($category->id, $foodPost->category_id);
        $this->assertSame(20, $foodPost->jumlah_porsi);
        $this->assertSame(20, $foodPost->sisa_porsi);
        $this->assertSame(FoodPostStatus::Tersedia, $foodPost->status);
        Storage::disk('public')->assertExists($foodPost->foto);
    }

    public function test_validasi_kolom_wajib_posting_makanan(): void
    {
        [$donorUser] = $this->createDonor();
        FoodCategory::create(['nama_kategori' => 'Karbohidrat']);

        $this->browse(function (Browser $browser) use ($donorUser) {
            $browser->loginAs($donorUser)
                ->visit('/donor/food-posts/create');

            $browser->script("document.querySelector('form').setAttribute('novalidate','novalidate'); document.querySelectorAll('[required]').forEach(function (el) { el.removeAttribute('required'); });");
            $browser->clear('judul')
                    ->clear('deskripsi')
                    ->clear('jumlah_porsi')
                    ->clear('alamat')
                    ->clear('batas_konsumsi');

            $browser->press('Terbitkan Postingan Sekarang')
                ->waitForText('Periksa kembali data postingan Anda.')
                ->assertSee('judul wajib diisi.')
                ->assertSee('deskripsi wajib diisi.')
                ->assertSee('foto wajib diisi.')
                ->assertSee('jumlah porsi wajib diisi.')
                ->assertSee('batas konsumsi wajib diisi.')
                ->assertSee('alamat wajib diisi.')
                ->assertSee('kategori wajib diisi.');
        });

        $this->assertDatabaseCount('food_posts', 0);
    }

    public function test_donor_dapat_melihat_daftar_postingannya(): void
    {
        [$donorUser, $donorProfile] = $this->createDonor();
        $otherDonor = $this->createDonorProfile();
        $category = FoodCategory::create(['nama_kategori' => 'Sayuran']);
        $ownTitle = 'Postingan Milik Sendiri '.uniqid();
        $otherTitle = 'Postingan Donor Lain '.uniqid();

        FoodPost::create([
            'judul' => $ownTitle,
            'deskripsi' => 'Deskripsi milik sendiri',
            'foto' => 'food-posts/test.jpg',
            'jumlah_porsi' => 10,
            'sisa_porsi' => 10,
            'alamat' => 'Jl. Milik Sendiri',
            'lat' => -6.2,
            'lng' => 106.8,
            'batas_konsumsi' => now()->addDay(),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donorProfile->id,
            'category_id' => $category->id,
        ]);

        FoodPost::create([
            'judul' => $otherTitle,
            'deskripsi' => 'Deskripsi donor lain',
            'foto' => 'food-posts/test.jpg',
            'jumlah_porsi' => 10,
            'sisa_porsi' => 10,
            'alamat' => 'Jl. Donor Lain',
            'lat' => -6.2,
            'lng' => 106.8,
            'batas_konsumsi' => now()->addDay(),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $otherDonor->id,
            'category_id' => $category->id,
        ]);

        $this->browse(function (Browser $browser) use ($donorUser, $ownTitle, $otherTitle) {
            $browser->loginAs($donorUser)
                ->visit('/donor/food-posts')
                ->assertPathIs('/donor/food-posts')
                ->assertSee('Postingan Makanan')
                ->assertSee($ownTitle)
                ->assertDontSee($otherTitle);
        });
    }

    public function test_donor_dapat_mengedit_postingan(): void
    {
        [$donorUser, $donorProfile] = $this->createDonor();
        $category = FoodCategory::create(['nama_kategori' => 'Buah']);
        $oldTitle = 'Judul Lama '.uniqid();
        $newTitle = 'Judul Baru '.uniqid();

        $foodPost = FoodPost::create([
            'judul' => $oldTitle,
            'deskripsi' => 'Deskripsi lama',
            'foto' => 'food-posts/old.jpg',
            'jumlah_porsi' => 10,
            'sisa_porsi' => 10,
            'alamat' => 'Jl. Lama',
            'lat' => -6.2,
            'lng' => 106.8,
            'batas_konsumsi' => now()->addDay(),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donorProfile->id,
            'category_id' => $category->id,
        ]);

        $this->browse(function (Browser $browser) use ($donorUser, $foodPost, $newTitle) {
            $browser->loginAs($donorUser)
                ->visit("/donor/food-posts/{$foodPost->id}/edit")
                ->assertSee('Ubah Postingan Makanan')
                ->clear('judul')
                ->type('judul', $newTitle)
                ->clear('deskripsi')
                ->type('deskripsi', 'Deskripsi diperbarui')
                ->clear('jumlah_porsi')
                ->type('jumlah_porsi', '8')
                ->clear('alamat')
                ->type('alamat', 'Jl. Baru')
                ->press('Simpan Perubahan')
                ->waitForLocation('/donor/food-posts')
                ->waitForText('Postingan makanan berhasil diperbarui.')
                ->assertSee($newTitle);
        });

        $foodPost->refresh();
        $this->assertSame($newTitle, $foodPost->judul);
        $this->assertSame('Deskripsi diperbarui', $foodPost->deskripsi);
        $this->assertSame(8, $foodPost->jumlah_porsi);
        $this->assertSame(8, $foodPost->sisa_porsi);
    }

    public function test_donor_dapat_menghapus_postingan(): void
    {
        [$donorUser, $donorProfile] = $this->createDonor();
        $category = FoodCategory::create(['nama_kategori' => 'Minuman']);
        $title = 'Postingan Hapus '.uniqid();

        Storage::disk('public')->put('food-posts/delete-me.jpg', 'photo');

        $foodPost = FoodPost::create([
            'judul' => $title,
            'deskripsi' => 'Akan dihapus',
            'foto' => 'food-posts/delete-me.jpg',
            'jumlah_porsi' => 5,
            'sisa_porsi' => 5,
            'alamat' => 'Jl. Hapus',
            'lat' => -6.2,
            'lng' => 106.8,
            'batas_konsumsi' => now()->addDay(),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donorProfile->id,
            'category_id' => $category->id,
        ]);

        $this->browse(function (Browser $browser) use ($donorUser, $title) {
            $browser->loginAs($donorUser)
                ->visit('/donor/food-posts')
                ->assertSee($title)
                ->press('Hapus')
                ->acceptDialog()
                ->waitForText('Postingan makanan berhasil dihapus.')
                ->assertDontSee($title);
        });

        $this->assertDatabaseMissing('food_posts', ['id' => $foodPost->id]);
        Storage::disk('public')->assertMissing('food-posts/delete-me.jpg');
    }

    public function test_postingan_dengan_klaim_tidak_dapat_dihapus(): void
    {
        [$donorUser, $donorProfile] = $this->createDonor();
        $recipientProfile = $this->createRecipientProfile();
        $category = FoodCategory::create(['nama_kategori' => 'Lemak Sehat']);
        $title = 'Postingan Berklaim '.uniqid();

        $foodPost = FoodPost::create([
            'judul' => $title,
            'deskripsi' => 'Memiliki klaim',
            'foto' => 'food-posts/claimed.jpg',
            'jumlah_porsi' => 5,
            'sisa_porsi' => 5,
            'alamat' => 'Jl. Klaim',
            'lat' => -6.2,
            'lng' => 106.8,
            'batas_konsumsi' => now()->addDay(),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donorProfile->id,
            'category_id' => $category->id,
        ]);

        Claim::create([
            'post_id' => $foodPost->id,
            'recipient_id' => $recipientProfile->id,
            'jumlah_porsi' => 1,
            'status' => 'menunggu',
            'metode_pengambilan' => 'ambil_sendiri',
            'kode_verifikasi' => 'ABC123',
            'tanggal_klaim' => now()->toDateString(),
        ]);

        $this->browse(function (Browser $browser) use ($donorUser, $title) {
            $browser->loginAs($donorUser)
                ->visit('/donor/food-posts')
                ->assertSee($title)
                ->press('Hapus')
                ->acceptDialog()
                ->waitForText('Postingan makanan tidak dapat dihapus karena sudah memiliki klaim.')
                ->assertSee($title);
        });

        $this->assertDatabaseHas('food_posts', ['id' => $foodPost->id]);
    }

    private function createDonor(): array
    {
        $user = User::factory()->create([
            'email' => 'donor-'.uniqid().'@example.com',
            'role' => Role::Donor,
            'status_akun' => 'aktif',
        ]);

        $profile = $user->donorProfile()->create([
            'nama_usaha' => 'Usaha '.uniqid(),
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor No. 1, Jakarta',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        return [$user, $profile];
    }

    private function createDonorProfile(): DonorProfile
    {
        [, $profile] = $this->createDonor();

        return $profile;
    }

    private function createRecipientProfile(): RecipientProfile
    {
        $user = User::factory()->create([
            'email' => 'penerima-'.uniqid().'@example.com',
            'role' => Role::Penerima,
            'status_akun' => 'aktif',
        ]);

        return $user->recipientProfile()->create([
            'nama_lembaga' => 'Lembaga '.uniqid(),
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => 'Jl. Penerima No. 2, Jakarta',
            'lat' => -6.21,
            'lng' => 106.81,
        ]);
    }

    private function createTempJpeg(): string
    {
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'dusk-food-'.uniqid().'.jpg';
        $minimalJpegBase64 = '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';
        file_put_contents($path, base64_decode($minimalJpegBase64));

        return $path;
    }
}
