<?php

namespace Tests\Feature\Admin;

use App\Enums\AccountStatus;
use App\Enums\FoodPostStatus;
use App\Enums\Role;
use App\Models\Claim;
use App\Models\CourierDetail;
use App\Models\DonorProfile;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\FoodReview;
use App\Models\InAppNotification;
use App\Models\RecipientProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class ClaimManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_donor_and_recipient_cannot_manage_claims(): void
    {
        $donorProfile = $this->donorProfile();
        $post = $this->foodPost($donorProfile);
        $recipientProfile = $this->recipientProfile();
        $claim = $this->claim($post, $recipientProfile);

        foreach ([$this->activeUser(Role::Donor), $this->activeUser(Role::Penerima)] as $actor) {
            $this->actingAs($actor)->get(route('admin.claims.index'))->assertForbidden();
            $this->actingAs($actor)->get(route('admin.claims.create'))->assertForbidden();
            $this->actingAs($actor)->post(route('admin.claims.store'), [
                'recipient_id' => $recipientProfile->id,
                'post_id' => $post->id,
                'jumlah_porsi' => 1,
            ])->assertForbidden();
            $this->actingAs($actor)->get(route('admin.claims.edit', $claim))->assertForbidden();
            $this->actingAs($actor)->put(route('admin.claims.update', $claim), [
                'jumlah_porsi' => 1,
                'status' => 'pending',
            ])->assertForbidden();
            $this->actingAs($actor)->delete(route('admin.claims.destroy', $claim))->assertForbidden();
        }

        $this->assertDatabaseHas('claims', ['id' => $claim->id]);
    }

    public function test_administrator_can_search_and_filter_claims(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $donorProfile = $this->donorProfile(['nama_usaha' => 'Dapur Pencarian']);
        $post = $this->foodPost($donorProfile, ['judul' => 'Nasi Spesial']);
        $otherPost = $this->foodPost($donorProfile, ['judul' => 'Roti Biasa']);
        $recipient = $this->recipientProfile(['nama_lembaga' => 'Panti Unik']);
        $otherRecipient = $this->recipientProfile(['nama_lembaga' => 'Panti Lain']);

        $this->claim($post, $recipient, ['status' => 'pending', 'jumlah_porsi' => 2]);
        $this->claim($otherPost, $otherRecipient, ['status' => 'approved', 'jumlah_porsi' => 1]);

        $this->actingAs($admin)
            ->get(route('admin.claims.index', ['q' => 'Nasi Spesial']))
            ->assertOk()
            ->assertSee('Nasi Spesial')
            ->assertDontSee('Roti Biasa');

        $this->actingAs($admin)
            ->get(route('admin.claims.index', ['status' => 'disetujui']))
            ->assertOk()
            ->assertSee('Roti Biasa')
            ->assertDontSee('Nasi Spesial');

        $this->actingAs($admin)
            ->get(route('admin.claims.index', ['q' => 'Panti Unik']))
            ->assertOk()
            ->assertSee('Panti Unik')
            ->assertDontSee('Panti Lain');
    }

    public function test_claim_index_paginates_ten_claims_per_page(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $post = $this->foodPost($this->donorProfile());
        $recipient = $this->recipientProfile();

        for ($i = 1; $i <= 12; $i++) {
            $this->claim($post, $recipient, ['jumlah_porsi' => 1, 'status' => 'pending']);
        }

        $this->actingAs($admin)
            ->get(route('admin.claims.index'))
            ->assertOk()
            ->assertViewHas('claims', fn ($claims) => $claims->count() === 10 && $claims->total() === 12);

        $this->actingAs($admin)
            ->get(route('admin.claims.index', ['page' => 2]))
            ->assertOk()
            ->assertViewHas('claims', fn ($claims) => $claims->count() === 2);
    }

    public function test_administrator_can_create_a_pending_claim_and_notify_the_donor(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $donorProfile = $this->donorProfile();
        $post = $this->foodPost($donorProfile, ['sisa_porsi' => 10]);
        $recipient = $this->recipientProfile();

        $this->actingAs($admin)
            ->post(route('admin.claims.store'), [
                'recipient_id' => $recipient->id,
                'post_id' => $post->id,
                'jumlah_porsi' => 2,
            ])
            ->assertRedirect(route('admin.claims.index'))
            ->assertSessionHas('status');

        $claim = Claim::query()->sole();

        $this->assertSame('pending', $claim->status);
        $this->assertSame(2, $claim->jumlah_porsi);
        $this->assertSame(10, $post->fresh()->sisa_porsi);

        $notification = InAppNotification::query()->where('user_id', $donorProfile->user_id)->sole();
        $this->assertSame('Pengajuan Klaim Baru', $notification->judul);
        $this->assertSame('claim', $notification->tipe);
    }

    public function test_create_claim_rejects_expired_posts_and_excess_portions(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $post = $this->foodPost($this->donorProfile(), ['sisa_porsi' => 2]);
        $recipient = $this->recipientProfile();

        $this->actingAs($admin)
            ->post(route('admin.claims.store'), [
                'recipient_id' => $recipient->id,
                'post_id' => $post->id,
                'jumlah_porsi' => 3,
            ])
            ->assertSessionHasErrors('jumlah_porsi');

        $expired = $this->foodPost($this->donorProfile(), [
            'batas_konsumsi' => now()->subHour(),
            'sisa_porsi' => 5,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.claims.store'), [
                'recipient_id' => $recipient->id,
                'post_id' => $expired->id,
                'jumlah_porsi' => 1,
            ])
            ->assertSessionHasErrors('post_id');

        $this->assertDatabaseCount('claims', 0);
    }

    public function test_administrator_can_update_pending_claim_portions(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $post = $this->foodPost($this->donorProfile(), ['sisa_porsi' => 5]);
        $claim = $this->claim($post, $this->recipientProfile(), ['jumlah_porsi' => 1]);

        $this->actingAs($admin)
            ->put(route('admin.claims.update', $claim), [
                'jumlah_porsi' => 3,
                'status' => 'pending',
            ])
            ->assertRedirect(route('admin.claims.index'))
            ->assertSessionHas('status');

        $this->assertSame(3, $claim->fresh()->jumlah_porsi);
        $this->assertSame('pending', $claim->fresh()->status);
    }

    public function test_administrator_can_approve_a_claim_and_reduce_stock(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $post = $this->foodPost($this->donorProfile(), ['sisa_porsi' => 10]);
        $recipient = $this->recipientProfile();
        $claim = $this->claim($post, $recipient, ['jumlah_porsi' => 3]);

        $this->actingAs($admin)
            ->put(route('admin.claims.update', $claim), [
                'jumlah_porsi' => 3,
                'status' => 'approved',
            ])
            ->assertRedirect(route('admin.claims.index'));

        $fresh = $claim->fresh();

        $this->assertSame('approved', $fresh->status);
        $this->assertNotNull($fresh->approved_at);
        $this->assertSame(7, $post->fresh()->sisa_porsi);

        $notification = InAppNotification::query()->where('user_id', $recipient->user_id)->sole();
        $this->assertSame('Klaim Disetujui', $notification->judul);
        $this->assertStringContainsString('administrator', $notification->pesan);
    }

    public function test_administrator_can_reject_a_claim_without_reducing_stock(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $post = $this->foodPost($this->donorProfile(), ['sisa_porsi' => 10]);
        $recipient = $this->recipientProfile();
        $claim = $this->claim($post, $recipient, ['jumlah_porsi' => 3]);

        $this->actingAs($admin)
            ->put(route('admin.claims.update', $claim), [
                'jumlah_porsi' => 3,
                'status' => 'rejected',
            ])
            ->assertRedirect(route('admin.claims.index'));

        $this->assertSame('rejected', $claim->fresh()->status);
        $this->assertSame(10, $post->fresh()->sisa_porsi);
        $this->assertSame('Klaim Ditolak', InAppNotification::query()->where('user_id', $recipient->user_id)->sole()->judul);
    }

    public function test_processed_claims_cannot_be_updated(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $post = $this->foodPost($this->donorProfile(), ['sisa_porsi' => 10]);
        $claim = $this->claim($post, $this->recipientProfile(), ['status' => 'approved', 'jumlah_porsi' => 2]);

        $this->actingAs($admin)
            ->put(route('admin.claims.update', $claim), [
                'jumlah_porsi' => 2,
                'status' => 'pending',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Klaim yang sudah diproses tidak dapat diubah.');

        $this->assertSame('approved', $claim->fresh()->status);
        $this->assertSame(2, $claim->fresh()->jumlah_porsi);
    }

    public function test_completed_claim_cannot_be_deleted(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $post = $this->foodPost($this->donorProfile());
        $claim = $this->claim($post, $this->recipientProfile(), ['status' => 'completed']);

        $this->actingAs($admin)
            ->delete(route('admin.claims.destroy', $claim))
            ->assertRedirect(route('admin.claims.index'))
            ->assertSessionHas('error', 'Klaim yang sudah selesai tidak dapat dihapus.');

        $this->assertDatabaseHas('claims', ['id' => $claim->id]);
    }

    public function test_deleting_an_approved_claim_restores_stock_and_post_status(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $post = $this->foodPost($this->donorProfile(), [
            'jumlah_porsi' => 10,
            'sisa_porsi' => 0,
            'status' => FoodPostStatus::Habis,
        ]);
        $recipient = $this->recipientProfile();
        $claim = $this->claim($post, $recipient, ['status' => 'approved', 'jumlah_porsi' => 2]);

        $this->actingAs($admin)
            ->delete(route('admin.claims.destroy', $claim))
            ->assertRedirect(route('admin.claims.index'))
            ->assertSessionHas('status', 'Klaim berhasil dihapus.');

        $this->assertDatabaseMissing('claims', ['id' => $claim->id]);
        $this->assertSame(2, $post->fresh()->sisa_porsi);
        $this->assertSame(FoodPostStatus::Tersedia, $post->fresh()->status);
        $this->assertSame('Klaim Dihapus oleh Administrator', InAppNotification::query()->where('user_id', $recipient->user_id)->sole()->judul);
    }

    public function test_deleting_a_claim_cascades_courier_and_review_rows(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $post = $this->foodPost($this->donorProfile(), ['sisa_porsi' => 5]);
        $recipient = $this->recipientProfile();
        $claim = $this->claim($post, $recipient, ['status' => 'pending']);

        CourierDetail::create([
            'claim_id' => $claim->id,
            'platform' => 'GoSend',
            'nama_pengemudi' => 'Kurir Uji',
            'no_kendaraan' => 'B 1234 CD',
            'kode_verifikasi_kurir' => 'KURIR01',
            'foto_bukti' => 'seed/placeholder.webp',
        ]);

        FoodReview::create([
            'claim_id' => $claim->id,
            'rating' => 5,
            'komentar' => 'Ulasan uji',
            'jenis_feedback' => 'positif',
            'foto_bukti' => 'seed/placeholder.webp',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.claims.destroy', $claim))
            ->assertRedirect(route('admin.claims.index'));

        $this->assertDatabaseMissing('claims', ['id' => $claim->id]);
        $this->assertDatabaseMissing('courier_details', ['claim_id' => $claim->id]);
        $this->assertDatabaseMissing('food_reviews', ['claim_id' => $claim->id]);
        $this->assertSame(5, $post->fresh()->sisa_porsi);
    }

    private function activeUser(Role $role, ?string $name = null): User
    {
        return User::factory()->create([
            'nama' => $name ?? fake()->name(),
            'email' => $role->value.'-'.fake()->unique()->numerify('###').'@example.test',
            'role' => $role,
            'status_akun' => AccountStatus::Aktif,
        ]);
    }

    private function donorProfile(array $overrides = []): DonorProfile
    {
        $user = $this->activeUser(Role::Donor);

        return $user->donorProfile()->create($overrides + [
            'nama_usaha' => 'Dapur '.fake()->unique()->numerify('###'),
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor No. 1',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);
    }

    private function recipientProfile(array $overrides = []): RecipientProfile
    {
        $user = $this->activeUser(Role::Penerima);

        return $user->recipientProfile()->create($overrides + [
            'nama_lembaga' => 'Lembaga '.fake()->unique()->numerify('###'),
            'jenis_lembaga' => 'Yayasan',
            'alamat' => 'Jl. Penerima No. 1',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);
    }

    private function foodPost(DonorProfile $donor, array $overrides = []): FoodPost
    {
        $category = FoodCategory::query()->firstOrCreate(['nama_kategori' => 'Karbohidrat']);

        return FoodPost::create($overrides + [
            'judul' => 'Makanan '.fake()->unique()->numerify('###'),
            'deskripsi' => 'Deskripsi makanan uji',
            'foto' => 'seed/placeholder.webp',
            'jumlah_porsi' => 10,
            'sisa_porsi' => 10,
            'alamat' => 'Jl. Makanan No. 1',
            'lat' => -6.2,
            'lng' => 106.8,
            'batas_konsumsi' => now()->addDay(),
            'status' => FoodPostStatus::Tersedia,
            'donor_id' => $donor->id,
            'category_id' => $category->id,
        ]);
    }

    private function claim(FoodPost $post, RecipientProfile $recipient, array $overrides = []): Claim
    {
        return Claim::create($overrides + [
            'post_id' => $post->id,
            'recipient_id' => $recipient->id,
            'jumlah_porsi' => 1,
            'status' => 'pending',
            'tanggal_klaim' => today(),
        ]);
    }
}
