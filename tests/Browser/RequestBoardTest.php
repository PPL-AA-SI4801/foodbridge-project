<?php

namespace Tests\Browser;

use App\Enums\FoodPostStatus;
use App\Enums\FoodRequestStatus;
use App\Enums\Role;
use App\Enums\UrgencyLevel;
use App\Models\DonorProfile;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\FoodRequest;
use App\Models\RecipientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class RequestBoardTest extends DuskTestCase
{
    use DatabaseTruncation;

    /**
     * Penerima dapat membuka Request Board dan menyiarkan kebutuhan makanan baru.
     */
    public function test_penerima_can_create_food_request_from_request_board(): void
    {
        [$recipientUser] = $this->createRecipient();
        $category = FoodCategory::create(['nama_kategori' => 'Nasi & Lauk']);

        $this->browse(function (Browser $browser) use ($recipientUser, $category) {
            $browser->loginAs($recipientUser)
                ->visit('/recipient/requests')
                ->assertSee('Siarkan Kebutuhan Pangan')
                ->press('Buat Permintaan Kebutuhan')
                ->waitFor('#judul')
                ->type('judul', 'Nasi Kotak untuk Anak Panti')
                ->select('category_id', (string) $category->id)
                ->clear('jumlah_porsi')
                ->type('jumlah_porsi', '30')
                ->select('urgency_level', UrgencyLevel::Darurat->value)
                ->select('metode_penerimaan', 'diantar_kurir')
                ->type('deskripsi', 'Makanan halal tanpa pedas.')
                ->press('Siarkan ke Request Board')
                ->waitForText('Permintaan kebutuhan makanan berhasil disiarkan ke Request Board')
                ->assertPathIs('/recipient/requests')
                ->assertSee('Nasi Kotak untuk Anak Panti')
                ->assertSee('30 porsi dibutuhkan');
        });
    }

    /**
     * Donor dapat melihat permintaan terbuka di Request Board dan memenuhinya dengan postingan makanan.
     */
    public function test_donor_can_fulfil_open_request_on_request_board(): void
    {
        [$donorUser, $donorProfile] = $this->createDonor();
        [, $recipientProfile] = $this->createRecipient();
        $category = FoodCategory::create(['nama_kategori' => 'Makanan Siap Saji']);

        $foodPost = FoodPost::create([
            'judul' => 'Nasi Box Ayam Bakar',
            'deskripsi' => 'Siap santap',
            'foto' => 'food-posts/sample.jpg',
            'jumlah_porsi' => 40,
            'sisa_porsi' => 40,
            'alamat' => 'Jl. Donor No. 1',
            'lat' => -6.2,
            'lng' => 106.8,
            'batas_konsumsi' => now()->addHours(8),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donorProfile->id,
            'category_id' => $category->id,
        ]);

        $foodRequest = FoodRequest::create([
            'recipient_id' => $recipientProfile->id,
            'category_id' => $category->id,
            'judul' => 'Kebutuhan Makan Malam Panti',
            'deskripsi' => 'Untuk 25 anak panti.',
            'jumlah_porsi' => 25,
            'urgency_level' => UrgencyLevel::Darurat,
            'metode_penerimaan' => 'ambil_sendiri',
            'batas_waktu' => now()->addHours(5),
            'status' => FoodRequestStatus::Open,
        ]);

        $this->browse(function (Browser $browser) use ($donorUser, $foodRequest, $foodPost) {
            $browser->loginAs($donorUser)
                ->visit('/donor/requests')
                ->assertSee('Papan Kebutuhan Pangan Penerima')
                ->assertSee('Kebutuhan Makan Malam Panti')
                ->press('Penuhi Kebutuhan Ini')
                ->waitFor("#post_id_{$foodRequest->id}")
                ->select("#post_id_{$foodRequest->id}", (string) $foodPost->id)
                ->press('Konfirmasi Pemenuhan')
                ->waitForText('Terima kasih! Anda berhasil merespons kebutuhan makanan ini')
                ->assertPathIs('/donor/requests')
                ->assertSee('Sedang Diproses');
        });
    }

    /**
     * Administrator dapat memantau dan menutup permintaan kebutuhan di Request Board.
     */
    public function test_administrator_can_view_and_moderate_request_board(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Administrator,
            'status_akun' => 'aktif',
        ]);
        [, $recipientProfile] = $this->createRecipient();
        $category = FoodCategory::create(['nama_kategori' => 'Sembako']);

        FoodRequest::create([
            'recipient_id' => $recipientProfile->id,
            'category_id' => $category->id,
            'judul' => 'Permintaan Bantuan Pangan Mendesak',
            'jumlah_porsi' => 20,
            'urgency_level' => UrgencyLevel::Mendesak,
            'metode_penerimaan' => 'fleksibel',
            'batas_waktu' => now()->addHours(6),
            'status' => FoodRequestStatus::Open,
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/admin/requests')
                ->assertSee('Request Board')
                ->assertSee('Permintaan Bantuan Pangan Mendesak')
                ->press('Tutup')
                ->acceptDialog()
                ->waitForText('Permintaan kebutuhan makanan berhasil ditutup oleh Administrator')
                ->assertPathIs('/admin/requests')
                ->assertSee('Dibatalkan');
        });
    }

    /**
     * @return array{0: User, 1: DonorProfile}
     */
    private function createDonor(): array
    {
        $user = User::factory()->create([
            'role' => Role::Donor,
            'status_akun' => 'aktif',
        ]);

        $profile = $user->donorProfile()->create([
            'nama_usaha' => 'Resto Berkah '.fake()->unique()->numerify('###'),
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor No. 1, Jakarta',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        return [$user, $profile];
    }

    /**
     * @return array{0: User, 1: RecipientProfile}
     */
    private function createRecipient(): array
    {
        $user = User::factory()->create([
            'role' => Role::Penerima,
            'status_akun' => 'aktif',
        ]);

        $profile = $user->recipientProfile()->create([
            'nama_lembaga' => 'Panti Harapan '.fake()->unique()->numerify('###'),
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => 'Jl. Penerima No. 2, Jakarta',
            'lat' => -6.21,
            'lng' => 106.81,
        ]);

        return [$user, $profile];
    }
}
