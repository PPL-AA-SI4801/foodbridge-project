<?php

namespace Tests\Browser;

use App\Enums\FoodPostStatus;
use App\Enums\Role;
use App\Models\Claim;
use App\Models\CourierDetail;
use App\Models\DonorProfile;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\RecipientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class RecipientClaimsTest extends DuskTestCase
{
    use DatabaseTruncation;

    public function test_penerima_can_view_daily_quota_and_claim_list(): void
    {
        [$recipientUser, $recipient] = $this->createRecipient();
        $donor = $this->createDonor('Dapur Dusk');
        $category = FoodCategory::create(['nama_kategori' => 'Karbohidrat']);
        $post = $this->createFoodPost($donor, $category, 'Nasi Box Dusk');

        $this->createClaim($recipient, $post, [
            'status' => 'pending',
            'tanggal_klaim' => today(),
        ]);

        $this->browse(function (Browser $browser) use ($recipientUser) {
            $browser->loginAs($recipientUser)
                ->visit('/recipient/claims')
                ->assertSee('Klaim Saya')
                ->assertSee('Kuota Harian')
                ->assertAttribute('[data-quota]', 'data-quota', '2/3')
                ->assertSee('Nasi Box Dusk')
                ->assertSee('Dapur Dusk')
                ->assertSee('Bandung')
                ->assertSee('Menunggu');
        });
    }

    public function test_penerima_can_search_and_filter_claims(): void
    {
        [$recipientUser, $recipient] = $this->createRecipient();
        $donor = $this->createDonor('Dapur Filter');
        $otherDonor = $this->createDonor('Warung Lain');
        $category = FoodCategory::create(['nama_kategori' => 'Karbohidrat']);

        $matchingPost = $this->createFoodPost($donor, $category, 'Nasi Ayam Dusk');
        $otherPost = $this->createFoodPost($otherDonor, $category, 'Sup Sayur Dusk');

        $this->createClaim($recipient, $matchingPost, ['status' => 'pending', 'tanggal_klaim' => today()]);
        $this->createClaim($recipient, $otherPost, ['status' => 'completed', 'tanggal_klaim' => today()->subDay()]);

        $this->browse(function (Browser $browser) use ($recipientUser) {
            $browser->loginAs($recipientUser)
                ->visit('/recipient/claims')
                ->type('q', 'Ayam')
                ->select('status', 'menunggu')
                ->press('Terapkan')
                ->waitForText('Nasi Ayam Dusk')
                ->assertSee('Nasi Ayam Dusk')
                ->assertDontSee('Sup Sayur Dusk');
        });
    }

    public function test_penerima_can_open_the_claim_detail_modal(): void
    {
        [$recipientUser, $recipient] = $this->createRecipient();
        $donor = $this->createDonor('Dapur Detail');
        $category = FoodCategory::create(['nama_kategori' => 'Protein']);
        $post = $this->createFoodPost($donor, $category, 'Ayam Panggang Dusk');

        $claim = $this->createClaim($recipient, $post, [
            'jumlah_porsi' => 3,
            'status' => 'approved',
            'metode_pengambilan' => 'kurir_online',
            'kode_verifikasi' => '654321',
            'tanggal_klaim' => today(),
            'approved_at' => now(),
        ]);

        CourierDetail::create([
            'claim_id' => $claim->id,
            'platform' => 'GrabExpress',
            'nama_pengemudi' => 'Rizky Dusk',
            'no_kendaraan' => 'B 7788 DUSK',
            'kode_verifikasi_kurir' => 'DUSK88',
            'foto_bukti' => 'seed/placeholder.webp',
        ]);

        $this->browse(function (Browser $browser) use ($recipientUser, $claim) {
            $browser->loginAs($recipientUser)
                ->visit('/recipient/claims')
                ->click('@claim-detail-'.$claim->id)
                ->waitFor('@claim-detail-modal-'.$claim->id)
                ->assertVisible('@claim-detail-modal-'.$claim->id)
                ->assertSee('Ayam Panggang Dusk')
                ->assertSee('Dapur Detail')
                ->assertSee('3 porsi')
                ->assertSee('654321')
                ->assertSee('Rizky Dusk')
                ->assertSee('B 7788 DUSK')
                ->assertSee('DUSK88');
        });
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
            'nama_lembaga' => 'Panti Dusk '.fake()->unique()->numerify('###'),
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => 'Jl. Penerima Dusk No. 2, Jakarta',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        return [$user, $profile];
    }

    private function createDonor(string $name): DonorProfile
    {
        $user = User::factory()->create([
            'role' => Role::Donor,
            'status_akun' => 'aktif',
        ]);

        return $user->donorProfile()->create([
            'nama_usaha' => $name.' '.fake()->unique()->numerify('###'),
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor Dusk No. 1, Bandung',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);
    }

    private function createFoodPost(DonorProfile $donor, FoodCategory $category, string $title): FoodPost
    {
        return FoodPost::create([
            'judul' => $title,
            'deskripsi' => 'Makanan untuk pengujian Dusk.',
            'foto' => 'seed/placeholder.webp',
            'jumlah_porsi' => 10,
            'sisa_porsi' => 10,
            'alamat' => $donor->alamat,
            'lat' => $donor->lat,
            'lng' => $donor->lng,
            'batas_konsumsi' => now()->addDay(),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donor->id,
            'category_id' => $category->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createClaim(RecipientProfile $recipient, FoodPost $post, array $overrides = []): Claim
    {
        return Claim::create(array_merge([
            'jumlah_porsi' => 1,
            'status' => 'pending',
            'metode_pengambilan' => null,
            'kode_verifikasi' => null,
            'tanggal_klaim' => today(),
            'approved_at' => null,
            'completed_at' => null,
            'post_id' => $post->id,
            'recipient_id' => $recipient->id,
        ], $overrides));
    }
}
