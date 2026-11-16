<?php

namespace Tests\Browser;

use App\Enums\AccountStatus;
use App\Enums\FoodPostStatus;
use App\Enums\Role;
use App\Models\Claim;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DonorClaimTest extends DuskTestCase
{
    use DatabaseMigrations;

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function createDonor(string $email = 'donor.dusk@foodbridge.test'): array
    {
        $user = User::factory()->create([
            'nama' => 'Dapur Donor Test',
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => Role::Donor,
            'status_akun' => AccountStatus::Aktif,
        ]);

        $profile = $user->donorProfile()->create([
            'nama_usaha' => 'Dapur Donor Test '.uniqid(),
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor No. 1, Jakarta',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        return [$user, $profile];
    }

    private function createRecipient(string $email = 'penerima.dusk@foodbridge.test'): array
    {
        $user = User::factory()->create([
            'nama' => 'Panti Penerima Test',
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => Role::Penerima,
            'status_akun' => AccountStatus::Aktif,
        ]);

        $profile = $user->recipientProfile()->create([
            'nama_lembaga' => 'Panti Penerima Test',
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => 'Jl. Penerima No. 2, Jakarta',
            'lat' => -6.21,
            'lng' => 106.81,
        ]);

        return [$user, $profile];
    }

    private function createFoodPost($donorProfile, $category, array $overrides = []): FoodPost
    {
        return FoodPost::create(array_merge([
            'judul' => 'Nasi Kotak Donasi E2E',
            'deskripsi' => 'Makanan untuk uji persetujuan',
            'foto' => 'food-posts/sample.jpg',
            'jumlah_porsi' => 20,
            'sisa_porsi' => 20,
            'alamat' => 'Jl. Donor No. 1, Jakarta',
            'lat' => -6.2,
            'lng' => 106.8,
            'batas_konsumsi' => now()->addHours(6),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donorProfile->id,
            'category_id' => $category->id,
        ], $overrides));
    }

    private function createClaim($post, $recipientProfile, int $qty, string $status = 'pending'): Claim
    {
        return Claim::create([
            'post_id' => $post->id,
            'recipient_id' => $recipientProfile->id,
            'jumlah_porsi' => $qty,
            'status' => $status,
            'tanggal_klaim' => now()->toDateString(),
        ]);
    }

    // ---------------------------------------------------------------------
    // TC-09-01 : Daftar hanya klaim pending milik Donor
    // ---------------------------------------------------------------------
    public function testDonorOnlySeesOwnPendingClaims(): void
    {
        $category = FoodCategory::create(['nama_kategori' => 'Makanan Siap Saji', 'slug' => 'makanan-siap-saji']);

        [$donorAUser, $donorAProfile] = $this->createDonor('donor-a.dusk@foodbridge.test');
        [$donorBUser, $donorBProfile] = $this->createDonor('donor-b.dusk@foodbridge.test');
        [$recipientUser, $recipientProfile] = $this->createRecipient();

        $postA = $this->createFoodPost($donorAProfile, $category, ['judul' => 'Makanan Donor A']);
        $postB = $this->createFoodPost($donorBProfile, $category, ['judul' => 'Makanan Donor B']);

        $this->createClaim($postA, $recipientProfile, 3, 'pending');
        $this->createClaim($postB, $recipientProfile, 2, 'pending');

        $this->browse(function (Browser $browser) use ($donorAUser, $postA, $postB) {
            $browser->loginAs($donorAUser)
                ->visit('/donor/claims')
                ->assertSee('Kelola Klaim Masuk')
                ->assertSee($postA->judul)
                ->assertDontSee($postB->judul);
        });
    }

    // ---------------------------------------------------------------------
    // TC-09-02 : Setujui klaim
    // ---------------------------------------------------------------------
    public function testDonorCanApproveClaim(): void
    {
        $category = FoodCategory::create(['nama_kategori' => 'Makanan Siap Saji', 'slug' => 'makanan-siap-saji']);
        [$donorUser, $donorProfile] = $this->createDonor();
        [$recipientUser, $recipientProfile] = $this->createRecipient();

        $foodPost = $this->createFoodPost($donorProfile, $category, ['sisa_porsi' => 20, 'jumlah_porsi' => 20]);
        $claim = $this->createClaim($foodPost, $recipientProfile, 5, 'pending');

        $this->browse(function (Browser $browser) use ($donorUser, $foodPost, $recipientProfile) {
            $browser->loginAs($donorUser)
                ->visit('/donor/claims')
                ->assertSee('Kelola Klaim Masuk')
                ->assertSee($foodPost->judul)
                ->assertSee($recipientProfile->nama_lembaga)
                ->assertSee('5 porsi')
                ->press('Setujui')
                ->waitForText('Klaim berhasil disetujui dan sisa porsi telah diperbarui.')
                ->assertSee('Klaim berhasil disetujui dan sisa porsi telah diperbarui.');
        });

        $claim->refresh();
        $this->assertSame('approved', $claim->status);
        $this->assertNotNull($claim->approved_at);
        $this->assertSame(15, (int) FoodPost::find($foodPost->id)->sisa_porsi);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $recipientUser->id,
            'tipe' => 'claim',
        ]);
    }

    // ---------------------------------------------------------------------
    // TC-09-03 : Tolak klaim
    // ---------------------------------------------------------------------
    public function testDonorCanRejectClaim(): void
    {
        $category = FoodCategory::create(['nama_kategori' => 'Makanan Siap Saji', 'slug' => 'makanan-siap-saji']);
        [$donorUser, $donorProfile] = $this->createDonor();
        [$recipientUser, $recipientProfile] = $this->createRecipient();

        $foodPost = $this->createFoodPost($donorProfile, $category, ['sisa_porsi' => 20, 'jumlah_porsi' => 20]);
        $claim = $this->createClaim($foodPost, $recipientProfile, 5, 'pending');

        $this->browse(function (Browser $browser) use ($donorUser, $foodPost, $recipientProfile) {
            $browser->loginAs($donorUser)
                ->visit('/donor/claims')
                ->assertSee('Kelola Klaim Masuk')
                ->assertSee($foodPost->judul)
                ->assertSee($recipientProfile->nama_lembaga)
                ->press('Tolak')
                ->acceptDialog()
                ->waitForText('Klaim berhasil ditolak.')
                ->assertSee('Klaim berhasil ditolak.');
        });

        $claim->refresh();
        $this->assertSame('rejected', $claim->status);
        $this->assertSame(20, (int) FoodPost::find($foodPost->id)->sisa_porsi);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $recipientUser->id,
            'tipe' => 'claim',
        ]);
    }

    // ---------------------------------------------------------------------
    // TC-09-04 : Klaim milik post Donor lain -> 403
    // ---------------------------------------------------------------------
    public function testDonorCannotProcessAnotherDonorsClaim(): void
    {
        $category = FoodCategory::create(['nama_kategori' => 'Makanan Siap Saji', 'slug' => 'makanan-siap-saji']);
        [$donorAUser, $donorAProfile] = $this->createDonor('donor-a.dusk@foodbridge.test');
        [$donorBUser, $donorBProfile] = $this->createDonor('donor-b.dusk@foodbridge.test');
        [$recipientUser, $recipientProfile] = $this->createRecipient();

        $postA = $this->createFoodPost($donorAProfile, $category);
        $claim = $this->createClaim($postA, $recipientProfile, 4, 'pending');

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->actingAs($donorBUser);

        $response = $this->patch(route('donor.claims.approve', $claim));
        $response->assertForbidden();

        $claim->refresh();
        $this->assertSame('pending', $claim->status);
    }

    // ---------------------------------------------------------------------
    // TC-09-05 : Klaim sudah diproses tidak dapat diproses ulang
    // ---------------------------------------------------------------------
    public function testAlreadyProcessedClaimCannotBeReprocessed(): void
    {
        $category = FoodCategory::create(['nama_kategori' => 'Makanan Siap Saji', 'slug' => 'makanan-siap-saji']);
        [$donorUser, $donorProfile] = $this->createDonor();
        [$recipientUser, $recipientProfile] = $this->createRecipient();

        $foodPost = $this->createFoodPost($donorProfile, $category, ['sisa_porsi' => 20, 'jumlah_porsi' => 20]);
        $claim = $this->createClaim($foodPost, $recipientProfile, 5, 'pending');

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->actingAs($donorUser);

        $first = $this->patch(route('donor.claims.approve', $claim));
        $first->assertRedirect();
        $this->assertSame(15, (int) FoodPost::find($foodPost->id)->sisa_porsi);

        $second = $this->patch(route('donor.claims.approve', $claim));
        $second->assertRedirect();
        $second->assertSessionHas('error');

        $claim->refresh();
        $this->assertSame('approved', $claim->status);
        $this->assertSame(15, (int) FoodPost::find($foodPost->id)->sisa_porsi);
    }

    // ---------------------------------------------------------------------
    // TC-09-06 : Sisa porsi tidak cukup
    // ---------------------------------------------------------------------
    public function testApproveFailsWhenInsufficientStock(): void
    {
        $category = FoodCategory::create(['nama_kategori' => 'Makanan Siap Saji', 'slug' => 'makanan-siap-saji']);
        [$donorUser, $donorProfile] = $this->createDonor();
        [$recipientUser, $recipientProfile] = $this->createRecipient();

        $foodPost = $this->createFoodPost($donorProfile, $category, ['sisa_porsi' => 3, 'jumlah_porsi' => 10]);
        $claim = $this->createClaim($foodPost, $recipientProfile, 5, 'pending');

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->actingAs($donorUser);

        $response = $this->patch(route('donor.claims.approve', $claim));
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $claim->refresh();
        $this->assertSame('pending', $claim->status);
        $this->assertSame(3, (int) FoodPost::find($foodPost->id)->sisa_porsi);
    }

    // ---------------------------------------------------------------------
    // TC-09-07 : Makanan sudah kedaluwarsa
    // ---------------------------------------------------------------------
    public function testApproveFailsWhenFoodExpired(): void
    {
        $category = FoodCategory::create(['nama_kategori' => 'Makanan Siap Saji', 'slug' => 'makanan-siap-saji']);
        [$donorUser, $donorProfile] = $this->createDonor();
        [$recipientUser, $recipientProfile] = $this->createRecipient();

        $foodPost = $this->createFoodPost($donorProfile, $category, [
            'batas_konsumsi' => now()->subHour(),
            'sisa_porsi' => 20,
        ]);
        $claim = $this->createClaim($foodPost, $recipientProfile, 2, 'pending');

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->actingAs($donorUser);

        $response = $this->patch(route('donor.claims.approve', $claim));
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $claim->refresh();
        $this->assertSame('pending', $claim->status);
    }

    // ---------------------------------------------------------------------
    // TC-09-08 : Peran bukan Donor -> 403
    // ---------------------------------------------------------------------
    public function testNonDonorCannotApproveClaim(): void
    {
        $category = FoodCategory::create(['nama_kategori' => 'Makanan Siap Saji', 'slug' => 'makanan-siap-saji']);
        [$donorUser, $donorProfile] = $this->createDonor();
        [$recipientUser, $recipientProfile] = $this->createRecipient();

        $foodPost = $this->createFoodPost($donorProfile, $category);
        $claim = $this->createClaim($foodPost, $recipientProfile, 2, 'pending');

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->actingAs($recipientUser);

        $response = $this->patch(route('donor.claims.approve', $claim));
        $response->assertForbidden();

        $claim->refresh();
        $this->assertSame('pending', $claim->status);
    }

    // ---------------------------------------------------------------------
    // TC-09-09 : Klaim tidak ditemukan -> 404
    // ---------------------------------------------------------------------
    public function testApproveNonExistentClaimReturns404(): void
    {
        [$donorUser, $donorProfile] = $this->createDonor();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->actingAs($donorUser);

        $response = $this->patch('/donor/claims/999999/approve');
        $response->assertNotFound();
    }

    // ---------------------------------------------------------------------
    // TC-09-10 : Dua persetujuan tidak membuat stok negatif
    // ---------------------------------------------------------------------
    public function testConcurrentApprovalsDoNotMakeStockNegative(): void
    {
        $category = FoodCategory::create(['nama_kategori' => 'Makanan Siap Saji', 'slug' => 'makanan-siap-saji']);
        [$donorUser, $donorProfile] = $this->createDonor();
        [$recipientUser, $recipientProfile] = $this->createRecipient();

        $foodPost = $this->createFoodPost($donorProfile, $category, ['sisa_porsi' => 10, 'jumlah_porsi' => 10]);
        $claim1 = $this->createClaim($foodPost, $recipientProfile, 6, 'pending');
        $claim2 = $this->createClaim($foodPost, $recipientProfile, 6, 'pending');

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->actingAs($donorUser);

        $first = $this->patch(route('donor.claims.approve', $claim1));
        $first->assertRedirect();
        $this->assertSame(4, (int) FoodPost::find($foodPost->id)->sisa_porsi);

        $second = $this->patch(route('donor.claims.approve', $claim2));
        $second->assertRedirect();
        $second->assertSessionHas('error');

        $this->assertSame(4, (int) FoodPost::find($foodPost->id)->sisa_porsi);
        $claim2->refresh();
        $this->assertSame('pending', $claim2->status);
    }
}