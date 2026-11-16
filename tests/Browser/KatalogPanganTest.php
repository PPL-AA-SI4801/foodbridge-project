<?php

namespace Tests\Browser;

use App\Enums\FoodPostStatus;
use App\Enums\Role;
use App\Models\DonorProfile;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\RecipientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class KatalogPanganTest extends DuskTestCase
{
    use DatabaseTruncation;

    public function test_penerima_dapat_membuka_katalog_pangan(): void
    {
        [$recipientUser] = $this->createRecipient();

        $this->browse(function (Browser $browser) use ($recipientUser) {
            $browser->loginAs($recipientUser)
                ->visit('/penerima/katalog')
                ->assertPathIs('/penerima/katalog')
                ->assertSee('Katalog Peta Pangan')
                ->assertPresent('#catalog-search')
                ->assertPresent('#category_id')
                ->assertPresent('#radius_km')
                ->assertPresent('#food-catalog-map');
        });
    }

    public function test_katalog_menampilkan_postingan_tersedia(): void
    {
        [$recipientUser] = $this->createRecipient();
        [, $donorProfile] = $this->createDonor();
        $category = FoodCategory::create(['nama_kategori' => 'Karbohidrat']);
        $title = 'Nasi Tersedia '.uniqid();

        FoodPost::create([
            'judul' => $title,
            'deskripsi' => 'Tersedia',
            'foto' => 'food-posts/test.jpg',
            'jumlah_porsi' => 10,
            'sisa_porsi' => 10,
            'alamat' => 'Jl. Tersedia',
            'lat' => -6.201,
            'lng' => 106.801,
            'batas_konsumsi' => now()->addDay(),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donorProfile->id,
            'category_id' => $category->id,
        ]);

        $this->browse(function (Browser $browser) use ($recipientUser, $title) {
            $browser->loginAs($recipientUser)
                ->visit('/penerima/katalog')
                ->waitForText($title)
                ->assertSee($title)
                ->assertSee('donasi aktif');
        });
    }

    public function test_penerima_dapat_mencari_makanan_berdasarkan_kata_kunci(): void
    {
        [$recipientUser] = $this->createRecipient();
        [, $donorProfile] = $this->createDonor();
        $category = FoodCategory::create(['nama_kategori' => 'Protein']);
        $matchingTitle = 'Nasi Ayam Bakar '.uniqid();
        $otherTitle = 'Roti Gandum '.uniqid();

        FoodPost::create([
            'judul' => $matchingTitle,
            'deskripsi' => 'Ayam bakar spesial',
            'foto' => 'food-posts/test.jpg',
            'jumlah_porsi' => 10,
            'sisa_porsi' => 10,
            'alamat' => 'Jl. Melati',
            'lat' => -6.202,
            'lng' => 106.802,
            'batas_konsumsi' => now()->addDay(),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donorProfile->id,
            'category_id' => $category->id,
        ]);

        FoodPost::create([
            'judul' => $otherTitle,
            'deskripsi' => 'Roti sehat',
            'foto' => 'food-posts/test.jpg',
            'jumlah_porsi' => 10,
            'sisa_porsi' => 10,
            'alamat' => 'Jl. Anggrek',
            'lat' => -6.203,
            'lng' => 106.803,
            'batas_konsumsi' => now()->addDay(),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donorProfile->id,
            'category_id' => $category->id,
        ]);

        $this->browse(function (Browser $browser) use ($recipientUser, $matchingTitle, $otherTitle) {
            $browser->loginAs($recipientUser)
                ->visit('/penerima/katalog')
                ->type('q', 'Ayam')
                ->press('Cari')
                ->waitForText($matchingTitle)
                ->assertSee($matchingTitle)
                ->assertDontSee($otherTitle);
        });
    }

    public function test_penerima_dapat_memfilter_berdasarkan_kategori(): void
    {
        [$recipientUser] = $this->createRecipient();
        [, $donorProfile] = $this->createDonor();
        $protein = FoodCategory::create(['nama_kategori' => 'Protein']);
        $sayuran = FoodCategory::create(['nama_kategori' => 'Sayuran']);
        $proteinTitle = 'Telur Balado '.uniqid();
        $vegetableTitle = 'Sup Bayam '.uniqid();

        FoodPost::create([
            'judul' => $proteinTitle,
            'deskripsi' => 'Telur',
            'foto' => 'food-posts/test.jpg',
            'jumlah_porsi' => 10,
            'sisa_porsi' => 10,
            'alamat' => 'Jl. Protein',
            'lat' => -6.204,
            'lng' => 106.804,
            'batas_konsumsi' => now()->addDay(),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donorProfile->id,
            'category_id' => $protein->id,
        ]);

        FoodPost::create([
            'judul' => $vegetableTitle,
            'deskripsi' => 'Bayam',
            'foto' => 'food-posts/test.jpg',
            'jumlah_porsi' => 10,
            'sisa_porsi' => 10,
            'alamat' => 'Jl. Sayur',
            'lat' => -6.205,
            'lng' => 106.805,
            'batas_konsumsi' => now()->addDay(),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donorProfile->id,
            'category_id' => $sayuran->id,
        ]);

        $this->browse(function (Browser $browser) use ($recipientUser, $protein, $proteinTitle, $vegetableTitle) {
            $browser->loginAs($recipientUser)
                ->visit('/penerima/katalog')
                ->press('Filter')
                ->select('category_id', (string) $protein->id)
                ->press('Terapkan')
                ->waitForText($proteinTitle)
                ->assertSee($proteinTitle)
                ->assertDontSee($vegetableTitle)
                ->assertSee('Atur ulang');
        });
    }

    public function test_penerima_dapat_menggunakan_filter_radius_lokasi(): void
    {
        [$recipientUser, $recipientProfile] = $this->createRecipient(['lat' => -6.2, 'lng' => 106.8]);
        [, $donorProfile] = $this->createDonor();
        $category = FoodCategory::create(['nama_kategori' => 'Karbohidrat']);
        $nearTitle = 'Nasi Dalam Radius '.uniqid();
        $farTitle = 'Nasi Luar Radius '.uniqid();

        FoodPost::create([
            'judul' => $nearTitle,
            'deskripsi' => 'Dekat',
            'foto' => 'food-posts/test.jpg',
            'jumlah_porsi' => 10,
            'sisa_porsi' => 10,
            'alamat' => 'Jl. Dekat',
            'lat' => -6.21,
            'lng' => 106.81,
            'batas_konsumsi' => now()->addDay(),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donorProfile->id,
            'category_id' => $category->id,
        ]);

        FoodPost::create([
            'judul' => $farTitle,
            'deskripsi' => 'Jauh',
            'foto' => 'food-posts/test.jpg',
            'jumlah_porsi' => 10,
            'sisa_porsi' => 10,
            'alamat' => 'Jl. Jauh',
            'lat' => -6.4,
            'lng' => 107.0,
            'batas_konsumsi' => now()->addDay(),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donorProfile->id,
            'category_id' => $category->id,
        ]);

        $this->browse(function (Browser $browser) use ($recipientUser, $nearTitle, $farTitle) {
            $browser->loginAs($recipientUser)
                ->visit('/penerima/katalog')
                ->press('Filter')
                ->select('radius_km', '5')
                ->press('Terapkan')
                ->waitForText($nearTitle)
                ->assertSee($nearTitle)
                ->assertDontSee($farTitle)
                ->assertSee('Radius 5 km');
        });
    }

    public function test_katalog_hanya_menampilkan_makanan_memenuhi_kriteria(): void
    {
        [$recipientUser] = $this->createRecipient(['lat' => -6.2, 'lng' => 106.8]);
        [, $donorProfile] = $this->createDonor();
        $category = FoodCategory::create(['nama_kategori' => 'Sayuran']);
        $availableNearTitle = 'Tersedia Dekat '.uniqid();
        $availableFarTitle = 'Tersedia Jauh '.uniqid();
        $expiredTitle = 'Kedaluwarsa '.uniqid();
        $soldOutTitle = 'Habis '.uniqid();

        FoodPost::create([
            'judul' => $availableNearTitle,
            'deskripsi' => 'OK',
            'foto' => 'food-posts/test.jpg',
            'jumlah_porsi' => 10,
            'sisa_porsi' => 10,
            'alamat' => 'Jl. Dekat',
            'lat' => -6.21,
            'lng' => 106.81,
            'batas_konsumsi' => now()->addDay(),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donorProfile->id,
            'category_id' => $category->id,
        ]);

        FoodPost::create([
            'judul' => $availableFarTitle,
            'deskripsi' => 'Jauh',
            'foto' => 'food-posts/test.jpg',
            'jumlah_porsi' => 10,
            'sisa_porsi' => 10,
            'alamat' => 'Jl. Jauh',
            'lat' => -6.4,
            'lng' => 107.0,
            'batas_konsumsi' => now()->addDay(),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donorProfile->id,
            'category_id' => $category->id,
        ]);

        FoodPost::create([
            'judul' => $expiredTitle,
            'deskripsi' => 'Lewat',
            'foto' => 'food-posts/test.jpg',
            'jumlah_porsi' => 10,
            'sisa_porsi' => 10,
            'alamat' => 'Jl. Lewat',
            'lat' => -6.21,
            'lng' => 106.81,
            'batas_konsumsi' => now()->subMinute(),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donorProfile->id,
            'category_id' => $category->id,
        ]);

        FoodPost::create([
            'judul' => $soldOutTitle,
            'deskripsi' => 'Habis',
            'foto' => 'food-posts/test.jpg',
            'jumlah_porsi' => 10,
            'sisa_porsi' => 0,
            'alamat' => 'Jl. Habis',
            'lat' => -6.21,
            'lng' => 106.81,
            'batas_konsumsi' => now()->addDay(),
            'status' => FoodPostStatus::Habis,
            'donor_id' => $donorProfile->id,
            'category_id' => $category->id,
        ]);

        $this->browse(function (Browser $browser) use ($recipientUser, $availableNearTitle, $availableFarTitle, $expiredTitle, $soldOutTitle) {
            $browser->loginAs($recipientUser)
                ->visit('/penerima/katalog')
                ->press('Filter')
                ->select('radius_km', '5')
                ->press('Terapkan')
                ->waitForText($availableNearTitle)
                ->assertSee($availableNearTitle)
                ->assertDontSee($availableFarTitle)
                ->assertDontSee($expiredTitle)
                ->assertDontSee($soldOutTitle);
        });
    }

    public function test_peta_menampilkan_lokasi_postingan_makanan(): void
    {
        [$recipientUser] = $this->createRecipient();
        [, $donorProfile] = $this->createDonor();
        $category = FoodCategory::create(['nama_kategori' => 'Buah']);
        $title = 'Postingan Peta '.uniqid();

        FoodPost::create([
            'judul' => $title,
            'deskripsi' => 'Untuk peta',
            'foto' => 'food-posts/test.jpg',
            'jumlah_porsi' => 10,
            'sisa_porsi' => 10,
            'alamat' => 'Jl. Peta',
            'lat' => -6.22,
            'lng' => 106.82,
            'batas_konsumsi' => now()->addDay(),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donorProfile->id,
            'category_id' => $category->id,
        ]);

        $this->browse(function (Browser $browser) use ($recipientUser, $title) {
            $browser->loginAs($recipientUser)
                ->visit('/penerima/katalog')
                ->waitForText($title)
                ->assertPresent('#food-catalog-map')
                ->assertSee('1 titik makanan')
                ->pause(1500);

            $markerCount = $browser->script('return document.querySelectorAll(".leaflet-marker-icon").length;');
            \PHPUnit\Framework\Assert::assertGreaterThan(0, $markerCount[0] ?? 0);
        });
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

    private function createRecipient(array $overrides = []): array
    {
        $user = User::factory()->create([
            'email' => 'penerima-'.uniqid().'@example.com',
            'role' => Role::Penerima,
            'status_akun' => 'aktif',
        ]);

        $profile = $user->recipientProfile()->create(array_merge([
            'nama_lembaga' => 'Lembaga '.uniqid(),
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => 'Jl. Penerima No. 2, Jakarta',
            'lat' => -6.21,
            'lng' => 106.81,
        ], $overrides));

        return [$user, $profile];
    }
}
