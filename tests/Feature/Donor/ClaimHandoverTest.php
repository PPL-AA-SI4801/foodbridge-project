<?php

namespace Tests\Feature\Donor;

use App\Enums\FoodPostStatus;
use App\Enums\NotificationType;
use App\Enums\Role;
use App\Models\Claim;
use App\Models\DonorProfile;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\InAppNotification;
use App\Models\RecipientProfile;
use App\Models\User;
use App\Services\ClaimHandoverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class ClaimHandoverTest extends TestCase
{
    use RefreshDatabase;

    private ClaimHandoverService $handover;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handover = new ClaimHandoverService;
    }

    public function test_tc12_01_code_is_generated_for_approved_self_pickup_and_stays_unique(): void
    {
        [$recipient] = $this->recipientProfile();
        [$donor] = $this->donorProfile();
        $post = $this->foodPost($donor, $this->foodCategory());
        $claim = $this->claim($recipient, $post, [
            'status' => 'approved',
            'metode_pengambilan' => Claim::METHOD_AMBIL_SENDIRI,
            'kode_verifikasi' => null,
            'approved_at' => now(),
        ]);

        $code = $this->handover->ensureCode($claim);

        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $code);
        $this->assertSame($code, $claim->fresh()->kode_verifikasi);

        // Idempoten: panggilan kedua tidak mengganti kode.
        $this->assertSame($code, $this->handover->ensureCode($claim->fresh()));

        // Bukan ambil-sendiri atau belum approved: tidak dibuatkan kode.
        $courierClaim = $this->claim($recipient, $post, [
            'status' => 'approved',
            'metode_pengambilan' => Claim::METHOD_KURIR_ONLINE,
            'approved_at' => now(),
        ]);
        $pendingClaim = $this->claim($recipient, $post, [
            'status' => 'pending',
            'metode_pengambilan' => Claim::METHOD_AMBIL_SENDIRI,
        ]);

        $this->assertNull($this->handover->ensureCode($courierClaim));
        $this->assertNull($this->handover->ensureCode($pendingClaim));
    }

    public function test_tc12_01_code_visible_only_to_recipient_not_to_donor(): void
    {
        [$recipient, $recipientUser] = $this->recipientProfile();
        [$donor, $donorUser] = $this->donorProfile();
        $post = $this->foodPost($donor, $this->foodCategory());
        $claim = $this->claim($recipient, $post, [
            'status' => 'approved',
            'metode_pengambilan' => Claim::METHOD_AMBIL_SENDIRI,
            'kode_verifikasi' => '482913',
            'approved_at' => now(),
        ]);

        $this->actingAs($recipientUser)
            ->get(route('recipient.claims.index', ['claim' => $claim->id]))
            ->assertOk()
            ->assertSee('482913');

        $this->actingAs($donorUser)
            ->get(route('donor.claims.index', ['claim' => $claim->id]))
            ->assertOk()
            ->assertDontSee('482913')
            ->assertSee('Kode verifikasi dari penerima', false);
    }

    public function test_tc12_02_donor_completes_handover_with_correct_code(): void
    {
        [$recipient, $recipientUser] = $this->recipientProfile();
        [$donor, $donorUser] = $this->donorProfile();
        $post = $this->foodPost($donor, $this->foodCategory());
        $claim = $this->claim($recipient, $post, [
            'status' => 'approved',
            'metode_pengambilan' => Claim::METHOD_AMBIL_SENDIRI,
            'kode_verifikasi' => '482913',
            'approved_at' => now(),
        ]);

        $this->actingAs($donorUser)
            ->patch(route('donor.claims.complete', $claim), ['kode_verifikasi' => '482913'])
            ->assertRedirect()
            ->assertSessionHas('success', 'Makanan sudah diserahkan. Klaim selesai.');

        $this->assertDatabaseHas('claims', [
            'id' => $claim->id,
            'status' => 'completed',
        ]);
        $this->assertNotNull($claim->fresh()->completed_at);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $recipientUser->id,
            'judul' => 'Makanan Telah Diserahkan',
            'tipe' => NotificationType::Claim->value,
        ]);

        // Setelah selesai, kode tidak lagi tampil di halaman penerima.
        $this->actingAs($recipientUser)
            ->get(route('recipient.claims.index', ['claim' => $claim->id]))
            ->assertOk()
            ->assertDontSee('482913');
    }

    public function test_tc12_03_wrong_or_empty_code_is_rejected(): void
    {
        [$recipient] = $this->recipientProfile();
        [$donor, $donorUser] = $this->donorProfile();
        $post = $this->foodPost($donor, $this->foodCategory());
        $claim = $this->claim($recipient, $post, [
            'status' => 'approved',
            'metode_pengambilan' => Claim::METHOD_AMBIL_SENDIRI,
            'kode_verifikasi' => '482913',
            'approved_at' => now(),
        ]);

        $this->actingAs($donorUser)
            ->from(route('donor.claims.index'))
            ->patch(route('donor.claims.complete', $claim), ['kode_verifikasi' => '000000'])
            ->assertRedirect()
            ->assertSessionHas('error', 'Kode verifikasi tidak sesuai.');

        $this->actingAs($donorUser)
            ->from(route('donor.claims.index'))
            ->patch(route('donor.claims.complete', $claim), ['kode_verifikasi' => ''])
            ->assertRedirect()
            ->assertSessionHasErrors('kode_verifikasi');

        $this->assertSame('approved', $claim->fresh()->status);
        $this->assertSame('482913', $claim->fresh()->kode_verifikasi);
        $this->assertDatabaseMissing('notifications', [
            'judul' => 'Makanan Telah Diserahkan',
        ]);
    }

    public function test_tc12_04_repeated_completion_is_rejected_without_duplicate_notification(): void
    {
        [$recipient, $recipientUser] = $this->recipientProfile();
        [$donor, $donorUser] = $this->donorProfile();
        $post = $this->foodPost($donor, $this->foodCategory());
        $claim = $this->claim($recipient, $post, [
            'status' => 'approved',
            'metode_pengambilan' => Claim::METHOD_AMBIL_SENDIRI,
            'kode_verifikasi' => '482913',
            'approved_at' => now(),
        ]);

        $this->actingAs($donorUser)
            ->patch(route('donor.claims.complete', $claim), ['kode_verifikasi' => '482913'])
            ->assertRedirect()
            ->assertSessionHas('success');

        // Klik ganda / aksi ulang pada klaim yang sudah selesai.
        $this->actingAs($donorUser)
            ->from(route('donor.claims.index'))
            ->patch(route('donor.claims.complete', $claim->fresh()), ['kode_verifikasi' => '482913'])
            ->assertRedirect()
            ->assertSessionHas('error', 'Hanya klaim yang disetujui yang dapat diselesaikan.');

        $this->assertSame(1, InAppNotification::query()
            ->where('user_id', $recipientUser->id)
            ->where('judul', 'Makanan Telah Diserahkan')
            ->count());
    }

    public function test_tc12_05_other_donor_recipient_and_guest_cannot_complete_claim(): void
    {
        [$recipient, $recipientUser] = $this->recipientProfile();
        [$donor] = $this->donorProfile();
        [$otherDonor, $otherDonorUser] = $this->donorProfile();
        $post = $this->foodPost($donor, $this->foodCategory());
        $claim = $this->claim($recipient, $post, [
            'status' => 'approved',
            'metode_pengambilan' => Claim::METHOD_AMBIL_SENDIRI,
            'kode_verifikasi' => '482913',
            'approved_at' => now(),
        ]);

        // Tamu: redirect ke login (diuji pertama agar tidak terbawa actingAs).
        $this->patch(route('donor.claims.complete', $claim), ['kode_verifikasi' => '482913'])
            ->assertRedirect(route('login'));

        // Donor lain: 403.
        $this->actingAs($otherDonorUser)
            ->patch(route('donor.claims.complete', $claim), ['kode_verifikasi' => '482913'])
            ->assertForbidden();

        // Penerima: 403 via middleware role.
        $this->actingAs($recipientUser)
            ->patch(route('donor.claims.complete', $claim), ['kode_verifikasi' => '482913'])
            ->assertForbidden();

        $this->assertSame('approved', $claim->fresh()->status);
        $this->assertDatabaseMissing('notifications', [
            'judul' => 'Makanan Telah Diserahkan',
        ]);
    }

    /**
     * @return array{0: RecipientProfile, 1: User}
     */
    private function recipientProfile(array $overrides = []): array
    {
        $user = User::factory()->create([
            'role' => Role::Penerima,
            'status_akun' => 'aktif',
        ]);

        $recipient = $user->recipientProfile()->create(array_merge([
            'nama_lembaga' => fake()->company(),
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => 'Jl. Penerima No. 1, Bandung',
            'lat' => -6.9,
            'lng' => 107.6,
        ], $overrides));

        return [$recipient, $user];
    }

    /**
     * @return array{0: DonorProfile, 1: User}
     */
    private function donorProfile(array $overrides = []): array
    {
        $user = User::factory()->create([
            'role' => Role::Donor,
            'status_akun' => 'aktif',
        ]);

        $donor = $user->donorProfile()->create(array_merge([
            'nama_usaha' => fake()->unique()->company(),
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor No. 1, Bandung',
            'lat' => -6.2,
            'lng' => 106.8,
        ], $overrides));

        return [$donor, $user];
    }

    private function foodCategory(string $name = 'Karbohidrat'): FoodCategory
    {
        return FoodCategory::create(['nama_kategori' => $name.' '.uniqid()]);
    }

    private function foodPost(DonorProfile $donor, FoodCategory $category, array $overrides = []): FoodPost
    {
        return FoodPost::create(array_merge([
            'judul' => 'Nasi Box Handover '.uniqid(),
            'deskripsi' => 'Nasi box siap santap',
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
        ], $overrides));
    }

    private function claim(RecipientProfile $recipient, FoodPost $post, array $overrides = []): Claim
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
