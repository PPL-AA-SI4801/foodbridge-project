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

class ClaimManagementTest extends DuskTestCase
{
    use DatabaseMigrations;

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function createVerifiedDonor(string $email = 'donor.dusk@foodbridge.test'): array
    {
        $user = User::factory()->create([
            'nama' => 'Dapur Donor Test',
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => Role::Donor,
            'status_akun' => AccountStatus::Aktif,
        ]);

        $profile = $user->donorProfile()->create([
            'nama_usaha' => 'Dapur Donor Test',
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor No. 1, Jakarta',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        return [$user, $profile];
    }

    private function createVerifiedRecipient(string $email = 'penerima.dusk@foodbridge.test'): array
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
            'deskripsi' => 'Makanan untuk uji klaim',
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

    private function claimViaUi(Browser $browser, $recipient, FoodPost $foodPost, int $qty): void
    {
        $browser->loginAs($recipient)
            ->visit('/penerima/katalog')
            ->assertSee($foodPost->judul)
            ->click('button[aria-controls="claim-form-'.$foodPost->id.'"]')
            ->waitFor('#claim-form-'.$foodPost->id)
            ->type('#jumlah_porsi_'.$foodPost->id, (string) $qty);
    }

    // ---------------------------------------------------------------------
    // TC-07-01 : Klaim valid
    // ---------------------------------------------------------------------
    public function testRecipientCanClaimAvailableFood(): void
    {
        $category = FoodCategory::create(['nama_kategori' => 'Makanan Siap Saji', 'slug' => 'makanan-siap-saji']);
        [$donorUser, $donorProfile] = $this->createVerifiedDonor();
        [$recipientUser, $recipientProfile] = $this->createVerifiedRecipient();

        $foodPost = $this->createFoodPost($donorProfile, $category, ['sisa_porsi' => 20, 'jumlah_porsi' => 20]);

        $this->browse(function (Browser $browser) use ($recipientUser, $foodPost) {
            $this->claimViaUi($browser, $recipientUser, $foodPost, 5);
            $browser->press('Ajukan Klaim')
                ->waitForText('Klaim berhasil diajukan')
                ->assertPathIs('/recipient/claims')
                ->assertSee($foodPost->judul);
        });

        $this->assertDatabaseHas('claims', [
            'post_id' => $foodPost->id,
            'recipient_id' => $recipientProfile->id,
            'jumlah_porsi' => 5,
            'status' => 'pending',
        ]);
    }

    // ---------------------------------------------------------------------
    // TC-07-02 : Klaim sebesar sisa porsi
    // ---------------------------------------------------------------------
    public function testRecipientCanClaimExactRemainingPortions(): void
    {
        $category = FoodCategory::create(['nama_kategori' => 'Makanan Siap Saji', 'slug' => 'makanan-siap-saji']);
        [$donorUser, $donorProfile] = $this->createVerifiedDonor();
        [$recipientUser, $recipientProfile] = $this->createVerifiedRecipient();

        $foodPost = $this->createFoodPost($donorProfile, $category, ['sisa_porsi' => 20, 'jumlah_porsi' => 20]);

        $this->browse(function (Browser $browser) use ($recipientUser, $foodPost) {
            $this->claimViaUi($browser, $recipientUser, $foodPost, 20);
            $browser->press('Ajukan Klaim')
                ->waitForText('Klaim berhasil diajukan')
                ->assertPathIs('/recipient/claims');
        });

        $this->assertDatabaseHas('claims', [
            'post_id' => $foodPost->id,
            'recipient_id' => $recipientProfile->id,
            'jumlah_porsi' => 20,
            'status' => 'pending',
        ]);
    }

    // ---------------------------------------------------------------------
    // TC-07-03 : Klaim ke-3 tepat di batas kuota
    // ---------------------------------------------------------------------
    public function testRecipientThirdClaimReachesDailyQuota(): void
    {
        $category = FoodCategory::create(['nama_kategori' => 'Makanan Siap Saji', 'slug' => 'makanan-siap-saji']);
        [$donorUser, $donorProfile] = $this->createVerifiedDonor();
        [$recipientUser, $recipientProfile] = $this->createVerifiedRecipient();

        for ($i = 0; $i < 2; $i++) {
            $post = $this->createFoodPost($donorProfile, $category, ['judul' => "Post Kuota {$i}"]);
            Claim::create([
                'post_id' => $post->id,
                'recipient_id' => $recipientProfile->id,
                'jumlah_porsi' => 1,
                'status' => 'pending',
                'tanggal_klaim' => now()->toDateString(),
            ]);
        }

        $foodPost = $this->createFoodPost($donorProfile, $category, ['judul' => 'Post Klaim Ketiga']);

        $this->browse(function (Browser $browser) use ($recipientUser, $foodPost) {
            $this->claimViaUi($browser, $recipientUser, $foodPost, 3);
            $browser->press('Ajukan Klaim')
                ->waitForText('Klaim berhasil diajukan')
                ->visit('/recipient/claims')
                ->assertSee('Klaim Saya');
        });

        $this->assertSame(3, Claim::where('recipient_id', $recipientProfile->id)
            ->whereDate('tanggal_klaim', now()->toDateString())
            ->where('status', '!=', 'rejected')
            ->count());
    }

    // ---------------------------------------------------------------------
    // TC-07-04 : Klaim ke-4 melebihi kuota
    // ---------------------------------------------------------------------
    public function testRecipientFourthClaimIsRejectedByDailyQuota(): void
    {
        $category = FoodCategory::create(['nama_kategori' => 'Makanan Siap Saji', 'slug' => 'makanan-siap-saji']);
        [$donorUser, $donorProfile] = $this->createVerifiedDonor();
        [$recipientUser, $recipientProfile] = $this->createVerifiedRecipient();

        for ($i = 0; $i < 3; $i++) {
            $post = $this->createFoodPost($donorProfile, $category, ['judul' => "Post Kuota {$i}"]);
            Claim::create([
                'post_id' => $post->id,
                'recipient_id' => $recipientProfile->id,
                'jumlah_porsi' => 1,
                'status' => 'pending',
                'tanggal_klaim' => now()->toDateString(),
            ]);
        }

        $foodPost = $this->createFoodPost($donorProfile, $category, ['judul' => 'Post Klaim Keempat']);
        $before = Claim::where('recipient_id', $recipientProfile->id)->count();

        $this->browse(function (Browser $browser) use ($recipientUser, $foodPost) {
            $this->claimViaUi($browser, $recipientUser, $foodPost, 2);
            $browser->press('Ajukan Klaim')
                ->waitForText('Kuota klaim harian Anda sudah habis (3/3).')
                ->assertPathIs('/penerima/katalog');
        });

        $this->assertSame($before, Claim::where('recipient_id', $recipientProfile->id)->count());
    }

    // ---------------------------------------------------------------------
    // TC-07-05 : Porsi melebihi sisa (submit via JS to bypass HTML5 max)
    // ---------------------------------------------------------------------
    public function testRecipientCannotClaimMoreThanRemainingPortions(): void
    {
        $category = FoodCategory::create(['nama_kategori' => 'Makanan Siap Saji', 'slug' => 'makanan-siap-saji']);
        [$donorUser, $donorProfile] = $this->createVerifiedDonor();
        [$recipientUser, $recipientProfile] = $this->createVerifiedRecipient();

        $foodPost = $this->createFoodPost($donorProfile, $category, ['sisa_porsi' => 10, 'jumlah_porsi' => 10]);

        $before = Claim::where('recipient_id', $recipientProfile->id)->count();

        $this->browse(function (Browser $browser) use ($recipientUser, $foodPost) {
            $this->claimViaUi($browser, $recipientUser, $foodPost, 25);
            $browser->script('document.getElementById("claim-form-'.$foodPost->id.'").submit();');
            $browser->waitForText('Jumlah porsi yang diklaim tidak boleh melebihi sisa porsi makanan.')
                ->assertPathIs('/penerima/katalog');
        });

        $this->assertSame($before, Claim::where('recipient_id', $recipientProfile->id)->count());
    }

    // ---------------------------------------------------------------------
    // TC-07-06 : Input porsi tidak valid (server-side)
    // ---------------------------------------------------------------------
    public function testRecipientInvalidPortionInputIsRejected(): void
    {
        $category = FoodCategory::create(['nama_kategori' => 'Makanan Siap Saji', 'slug' => 'makanan-siap-saji']);
        [$donorUser, $donorProfile] = $this->createVerifiedDonor();
        [$recipientUser, $recipientProfile] = $this->createVerifiedRecipient();

        $foodPost = $this->createFoodPost($donorProfile, $category, ['sisa_porsi' => 10, 'jumlah_porsi' => 10]);

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->actingAs($recipientUser);

        foreach ([0, -2, 'abc'] as $value) {
            $response = $this->post(route('recipient.claims.store', $foodPost), [
                'jumlah_porsi' => $value,
            ]);
            $response->assertSessionHasErrors('jumlah_porsi');
        }

        $response = $this->post(route('recipient.claims.store', $foodPost), [
            'jumlah_porsi' => '',
        ]);
        $response->assertSessionHasErrors('jumlah_porsi');
    }

    // ---------------------------------------------------------------------
    // TC-07-07 : Makanan kedaluwarsa (server-side)
    // ---------------------------------------------------------------------
    public function testRecipientCannotClaimExpiredFood(): void
    {
        $category = FoodCategory::create(['nama_kategori' => 'Makanan Siap Saji', 'slug' => 'makanan-siap-saji']);
        [$donorUser, $donorProfile] = $this->createVerifiedDonor();
        [$recipientUser, $recipientProfile] = $this->createVerifiedRecipient();

        $foodPost = $this->createFoodPost($donorProfile, $category, [
            'batas_konsumsi' => now()->subHour(),
        ]);

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->actingAs($recipientUser);

        $response = $this->post(route('recipient.claims.store', $foodPost), [
            'jumlah_porsi' => 2,
        ]);

        $response->assertSessionHasErrors('jumlah_porsi');
    }

    // ---------------------------------------------------------------------
    // TC-07-08 : Makanan habis (tidak tampil di katalog)
    // ---------------------------------------------------------------------
    public function testRecipientCannotClaimEmptyFood(): void
    {
        $category = FoodCategory::create(['nama_kategori' => 'Makanan Siap Saji', 'slug' => 'makanan-siap-saji']);
        [$donorUser, $donorProfile] = $this->createVerifiedDonor();
        [$recipientUser, $recipientProfile] = $this->createVerifiedRecipient();

        $foodPost = $this->createFoodPost($donorProfile, $category, ['sisa_porsi' => 0, 'jumlah_porsi' => 10]);

        $before = Claim::where('recipient_id', $recipientProfile->id)->count();

        $this->browse(function (Browser $browser) use ($recipientUser, $foodPost) {
            $browser->loginAs($recipientUser)
                ->visit('/penerima/katalog')
                ->assertDontSee($foodPost->judul);
        });

        $this->assertSame($before, Claim::where('recipient_id', $recipientProfile->id)->count());
    }

    // ---------------------------------------------------------------------
    // TC-07-09 : Akun belum terverifikasi
    // ---------------------------------------------------------------------
    public function testPendingRecipientCannotAccessClaimFeature(): void
    {
        $pendingRecipient = User::factory()->create([
            'nama' => 'Panti Pending',
            'email' => 'pending.dusk@foodbridge.test',
            'password' => Hash::make('password'),
            'role' => Role::Penerima,
            'status_akun' => AccountStatus::Menunggu,
        ]);
        $pendingRecipient->recipientProfile()->create([
            'nama_lembaga' => 'Panti Pending',
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => 'Jl. Pending No. 3',
            'lat' => -6.22,
            'lng' => 106.82,
        ]);

        $this->browse(function (Browser $browser) use ($pendingRecipient) {
            $browser->loginAs($pendingRecipient)
                ->visit('/penerima/katalog')
                ->assertPathIs('/login')
                ->assertSee('menunggu verifikasi');
        });
    }

    // ---------------------------------------------------------------------
    // TC-07-12 : Klaim ganda pada post yang sama
    // ---------------------------------------------------------------------
    public function testDuplicateClaimOnSamePostIsRejected(): void
    {
        $category = FoodCategory::create(['nama_kategori' => 'Makanan Siap Saji', 'slug' => 'makanan-siap-saji']);
        [$donorUser, $donorProfile] = $this->createVerifiedDonor();
        [$recipientUser, $recipientProfile] = $this->createVerifiedRecipient();

        $foodPost = $this->createFoodPost($donorProfile, $category, ['sisa_porsi' => 20, 'jumlah_porsi' => 20]);

        Claim::create([
            'post_id' => $foodPost->id,
            'recipient_id' => $recipientProfile->id,
            'jumlah_porsi' => 2,
            'status' => 'pending',
            'tanggal_klaim' => now()->toDateString(),
        ]);

        $before = Claim::where('recipient_id', $recipientProfile->id)->count();

        $this->browse(function (Browser $browser) use ($recipientUser, $foodPost) {
            $this->claimViaUi($browser, $recipientUser, $foodPost, 3);
            $browser->press('Ajukan Klaim')
                ->waitForText('Anda sudah mengajukan klaim untuk makanan ini.')
                ->assertPathIs('/penerima/katalog');
        });

        $this->assertSame($before, Claim::where('recipient_id', $recipientProfile->id)->count());
    }

    // ---------------------------------------------------------------------
    // TC-07-11 & TC-07-10 : akses tanpa login / peran bukan Penerima
    // ---------------------------------------------------------------------
    public function testUnauthenticatedUserIsRedirectedToLogin(): void
    {
        $category = FoodCategory::create(['nama_kategori' => 'Makanan Siap Saji', 'slug' => 'makanan-siap-saji']);
        [$donorUser, $donorProfile] = $this->createVerifiedDonor();
        $foodPost = $this->createFoodPost($donorProfile, $category);

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

        $response = $this->post(route('recipient.claims.store', $foodPost), [
            'jumlah_porsi' => 2,
        ]);

        $response->assertRedirect('/login');
    }

    public function testNonRecipientRoleCannotClaim(): void
    {
        $category = FoodCategory::create(['nama_kategori' => 'Makanan Siap Saji', 'slug' => 'makanan-siap-saji']);
        [$donorUser, $donorProfile] = $this->createVerifiedDonor();
        $foodPost = $this->createFoodPost($donorProfile, $category);

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
        $this->actingAs($donorUser);

        $response = $this->post(route('recipient.claims.store', $foodPost), [
            'jumlah_porsi' => 2,
        ]);

        $response->assertForbidden();
    }
}