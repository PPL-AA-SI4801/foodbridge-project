<?php

namespace Tests\Feature\Admin;

use App\Enums\AccountStatus;
use App\Enums\FoodPostStatus;
use App\Enums\Role;
use App\Models\Claim;
use App\Models\DonorProfile;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\InAppNotification;
use App\Models\RecipientProfile;
use App\Models\User;
use App\Models\VerificationDocument;
use App\Services\Admin\UserManagementService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_view_donor_and_recipient_accounts(): void
    {
        $admin = $this->activeUser(Role::Administrator, 'Administrator Satu');
        $donor = $this->activeUser(Role::Donor, 'Donor Terdaftar', 'donor@example.test');
        $donor->donorProfile()->create([
            'nama_usaha' => 'Dapur Donor',
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor No. 1',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        $recipient = $this->activeUser(Role::Penerima, 'Penerima Terdaftar');
        $recipient->recipientProfile()->create([
            'nama_lembaga' => 'Panti Penerima',
            'jenis_lembaga' => 'Panti asuhan',
            'alamat' => 'Jl. Penerima No. 2',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk()
            ->assertSee('Manajemen Pengguna')
            ->assertSee('Administrator Satu')
            ->assertSee('Donor Terdaftar')
            ->assertSee('donor@example.test')
            ->assertSee('Dapur Donor')
            ->assertSee('Penerima Terdaftar')
            ->assertSee('Panti Penerima')
            ->assertViewHas('users', fn ($users) => $users->total() === 3);
    }

    public function test_administrator_can_search_and_filter_accounts(): void
    {
        $admin = $this->activeUser(Role::Administrator);

        $this->activeUser(Role::Donor, 'Donor Khusus');
        $blocked = $this->activeUser(Role::Penerima, 'Penerima Diblokir');
        $blocked->update(['status_akun' => AccountStatus::Diblokir]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', [
            'q' => 'Khusus',
            'status' => AccountStatus::Aktif->value,
        ]));

        $response->assertOk()
            ->assertSee('Donor Khusus')
            ->assertDontSee('Penerima Diblokir');
    }

    public function test_administrator_dashboard_shows_user_management_summary(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $this->activeUser(Role::Donor);
        $blocked = $this->activeUser(Role::Penerima);
        $blocked->update(['status_akun' => AccountStatus::Diblokir]);

        $this->actingAs($admin)
            ->get(route('dashboard.admin'))
            ->assertOk()
            ->assertSee('Manajemen Pengguna')
            ->assertSee('2 akun Donor/Penerima, 1 diblokir.');
    }

    public function test_administrator_can_block_an_active_donor_or_recipient(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $donor = $this->activeUser(Role::Donor);
        $recipient = $this->activeUser(Role::Penerima);

        $this->actingAs($admin)
            ->post(route('admin.users.block', $donor))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status', 'Akun berhasil diblokir.');

        $this->actingAs($admin)
            ->post(route('admin.users.block', $recipient))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status', 'Akun berhasil diblokir.');

        $this->assertSame(AccountStatus::Diblokir, $donor->fresh()->status_akun);
        $this->assertSame(AccountStatus::Diblokir, $recipient->fresh()->status_akun);
    }

    public function test_blocking_an_account_creates_an_account_notification(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $donor = $this->activeUser(Role::Donor);

        $this->actingAs($admin)
            ->post(route('admin.users.block', $donor))
            ->assertRedirect(route('admin.users.index'));

        $notification = InAppNotification::query()
            ->where('user_id', $donor->id)
            ->sole();

        $this->assertSame('Akun Anda Diblokir', $notification->judul);
        $this->assertSame('akun', $notification->tipe);
        $this->assertStringContainsString('telah diblokir oleh administrator', $notification->pesan);
        $this->assertFalse($notification->is_read);
    }

    public function test_blocking_an_account_deletes_its_active_sessions(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $donor = $this->activeUser(Role::Donor);

        DB::table('sessions')->insert([
            'id' => 'session-'.$donor->id,
            'user_id' => $donor->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.block', $donor))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('sessions', ['id' => 'session-'.$donor->id]);
    }

    public function test_a_blocked_account_loses_access_to_authenticated_pages(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $donor = $this->activeUser(Role::Donor);

        $this->actingAs($admin)
            ->post(route('admin.users.block', $donor));

        $this->actingAs($donor->refresh())
            ->get(route('dashboard.donor'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'account_status' => 'Akun Anda telah diblokir.',
            ]);
    }

    public function test_only_active_accounts_can_be_blocked(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $pending = $this->activeUser(Role::Donor);
        $pending->update(['status_akun' => AccountStatus::Menunggu]);

        $this->actingAs($admin)
            ->post(route('admin.users.block', $pending))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'Hanya akun aktif yang dapat diblokir.');

        $this->assertSame(AccountStatus::Menunggu, $pending->fresh()->status_akun);
    }

    public function test_already_blocked_accounts_cannot_be_blocked_again(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $blocked = $this->activeUser(Role::Penerima);
        $blocked->update(['status_akun' => AccountStatus::Diblokir]);

        $this->actingAs($admin)
            ->post(route('admin.users.block', $blocked))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'Akun ini sudah diblokir.');

        $this->assertSame(AccountStatus::Diblokir, $blocked->fresh()->status_akun);
    }

    public function test_administrator_accounts_cannot_be_blocked(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $otherAdmin = $this->activeUser(Role::Administrator);

        $this->actingAs($admin)
            ->post(route('admin.users.block', $otherAdmin))
            ->assertNotFound();

        $this->assertSame(AccountStatus::Aktif, $otherAdmin->fresh()->status_akun);
    }

    public function test_non_administrator_cannot_block_an_account(): void
    {
        $donor = $this->activeUser(Role::Donor);
        $otherDonor = $this->activeUser(Role::Donor);

        $this->actingAs($donor)
            ->post(route('admin.users.block', $otherDonor))
            ->assertForbidden();

        $this->assertSame(AccountStatus::Aktif, $otherDonor->fresh()->status_akun);
    }

    public function test_non_administrator_cannot_view_user_management(): void
    {
        $donor = $this->activeUser(Role::Donor);

        $this->actingAs($donor)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_user_management(): void
    {
        $this->get(route('admin.users.index'))
            ->assertRedirect(route('login'));
    }

    public function test_donor_and_recipient_cannot_manage_users(): void
    {
        $target = $this->activeUser(Role::Penerima, 'Target');

        foreach ([$this->activeUser(Role::Donor), $this->activeUser(Role::Penerima)] as $actor) {
            $this->actingAs($actor)->get(route('admin.users.index'))->assertForbidden();
            $this->actingAs($actor)->get(route('admin.users.create'))->assertForbidden();
            $this->actingAs($actor)->post(route('admin.users.store'), $this->donorPayload())->assertForbidden();
            $this->actingAs($actor)->get(route('admin.users.edit', $target))->assertForbidden();
            $this->actingAs($actor)->put(route('admin.users.update', $target), $this->recipientPayload())->assertForbidden();
            $this->actingAs($actor)->delete(route('admin.users.destroy', $target))->assertForbidden();
        }

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_administrator_can_create_a_donor_with_profile(): void
    {
        $admin = $this->activeUser(Role::Administrator);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), $this->donorPayload())
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status');

        $donor = User::query()->where('email', 'donor-baru@example.test')->sole();

        $this->assertSame(Role::Donor, $donor->role);
        $this->assertSame(AccountStatus::Aktif, $donor->status_akun);
        $this->assertTrue(Hash::check('rahasia123', $donor->password));
        $this->assertDatabaseHas('donor_profiles', [
            'user_id' => $donor->id,
            'nama_usaha' => 'Dapur Baru',
            'jenis_usaha' => 'Restoran',
        ]);
    }

    public function test_administrator_can_create_a_recipient_with_profile(): void
    {
        $admin = $this->activeUser(Role::Administrator);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), $this->recipientPayload())
            ->assertRedirect(route('admin.users.index'));

        $recipient = User::query()->where('email', 'penerima-baru@example.test')->sole();

        $this->assertSame(Role::Penerima, $recipient->role);
        $this->assertSame(AccountStatus::Aktif, $recipient->status_akun);
        $this->assertDatabaseHas('recipient_profiles', [
            'user_id' => $recipient->id,
            'nama_lembaga' => 'Yayasan Baru',
            'jenis_lembaga' => 'Yayasan',
        ]);
    }

    public function test_administrator_can_create_an_administrator_without_profile(): void
    {
        $admin = $this->activeUser(Role::Administrator);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'nama' => 'Admin Baru',
                'email' => 'admin-baru@example.test',
                'no_hp' => '081234567899',
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
                'role' => 'administrator',
            ])
            ->assertRedirect(route('admin.users.index'));

        $created = User::query()->where('email', 'admin-baru@example.test')->sole();

        $this->assertSame(Role::Administrator, $created->role);
        $this->assertDatabaseMissing('donor_profiles', ['user_id' => $created->id]);
        $this->assertDatabaseMissing('recipient_profiles', ['user_id' => $created->id]);
    }

    public function test_create_user_validates_unique_email_and_profile_fields(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $this->activeUser(Role::Donor, 'Donor Lama', 'dipakai@example.test');

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'nama' => 'Donor Duplikat',
                'email' => 'dipakai@example.test',
                'no_hp' => '081234567890',
                'password' => 'rahasia123',
                'password_confirmation' => 'beda-password',
                'role' => 'donor',
            ])
            ->assertSessionHasErrors(['email', 'password', 'nama_usaha', 'jenis_usaha', 'alamat', 'lat', 'lng']);
    }

    public function test_administrator_can_update_profile_without_changing_role_or_status(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $donor = $this->activeUser(Role::Donor, 'Donor Awal', 'donor-ubah@example.test');
        $donor->update(['password' => 'password-lama', 'status_akun' => AccountStatus::Diblokir]);
        $donor->donorProfile()->create([
            'nama_usaha' => 'Usaha Awal',
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Awal',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $donor), [
                'nama' => 'Donor Diperbarui',
                'email' => 'donor-ubah@example.test',
                'no_hp' => '089999999999',
                'password' => '',
                'password_confirmation' => '',
                'role' => 'administrator',
                'status_akun' => 'aktif',
                'nama_usaha' => 'Usaha Diperbarui',
                'jenis_usaha' => 'Katering',
                'alamat' => 'Jl. Diperbarui',
                'lat' => '-6.3',
                'lng' => '106.9',
            ])
            ->assertRedirect(route('admin.users.index'));

        $fresh = $donor->fresh();

        $this->assertSame('Donor Diperbarui', $fresh->nama);
        $this->assertSame('089999999999', $fresh->no_hp);
        $this->assertSame(Role::Donor, $fresh->role);
        $this->assertSame(AccountStatus::Diblokir, $fresh->status_akun);
        $this->assertTrue(Hash::check('password-lama', $fresh->password));
        $this->assertSame('Usaha Diperbarui', $fresh->donorProfile->nama_usaha);
    }

    public function test_administrator_can_change_a_password_when_provided(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $recipient = $this->activeUser(Role::Penerima, 'Penerima Ubah', 'penerima-ubah@example.test');
        $recipient->update(['password' => 'password-lama']);
        $recipient->recipientProfile()->create([
            'nama_lembaga' => 'Lembaga Awal',
            'jenis_lembaga' => 'Yayasan',
            'alamat' => 'Jl. Awal',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $recipient), [
                'nama' => $recipient->nama,
                'email' => $recipient->email,
                'no_hp' => $recipient->no_hp,
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
                'nama_lembaga' => 'Lembaga Awal',
                'jenis_lembaga' => 'Yayasan',
                'alamat' => 'Jl. Awal',
                'lat' => '-6.2',
                'lng' => '106.8',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertTrue(Hash::check('password-baru', $recipient->fresh()->password));
    }

    public function test_administrator_cannot_delete_their_own_account(): void
    {
        $admin = $this->activeUser(Role::Administrator);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'Anda tidak dapat menghapus akun yang sedang Anda gunakan.');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_last_administrator_cannot_be_deleted(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $actor = $this->activeUser(Role::Donor);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Administrator terakhir tidak dapat dihapus.');

        app(UserManagementService::class)->delete($admin, $actor);
    }

    public function test_donor_with_food_posts_cannot_be_deleted(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $donor = $this->activeUser(Role::Donor);
        $profile = $this->donorProfileFor($donor);
        $this->foodPostFor($profile);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $donor))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'Akun donor tidak dapat dihapus karena masih memiliki postingan makanan.');

        $this->assertDatabaseHas('users', ['id' => $donor->id]);
    }

    public function test_recipient_with_claims_cannot_be_deleted(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $donorProfile = $this->donorProfileFor($this->activeUser(Role::Donor));
        $post = $this->foodPostFor($donorProfile);
        $recipient = $this->activeUser(Role::Penerima);
        $recipientProfile = $this->recipientProfileFor($recipient);

        Claim::create([
            'post_id' => $post->id,
            'recipient_id' => $recipientProfile->id,
            'jumlah_porsi' => 1,
            'status' => 'pending',
            'tanggal_klaim' => today(),
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $recipient))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'Akun penerima tidak dapat dihapus karena masih memiliki klaim.');

        $this->assertDatabaseHas('users', ['id' => $recipient->id]);
    }

    public function test_administrator_can_delete_a_user_and_their_verification_documents(): void
    {
        Storage::fake('local');

        $admin = $this->activeUser(Role::Administrator);
        $recipient = $this->activeUser(Role::Penerima);
        $path = 'verification-documents/berkas.pdf';
        Storage::disk('local')->put($path, 'dokumen');

        VerificationDocument::create([
            'jenis_dokumen' => 'nib',
            'file_path' => $path,
            'status' => 'menunggu',
            'user_id' => $recipient->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $recipient))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('status', 'Akun berhasil dihapus.');

        $this->assertDatabaseMissing('users', ['id' => $recipient->id]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_index_filters_by_role_and_searches_profile_names(): void
    {
        $admin = $this->activeUser(Role::Administrator);
        $donor = $this->activeUser(Role::Donor, 'Donor Filter');
        $this->donorProfileFor($donor, ['nama_usaha' => 'Dapur Pencarian Unik']);
        $recipient = $this->activeUser(Role::Penerima, 'Penerima Filter');
        $this->recipientProfileFor($recipient, ['nama_lembaga' => 'Lembaga Pencarian Unik']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['role' => 'donor', 'q' => 'Pencarian Unik']))
            ->assertOk()
            ->assertSee('Dapur Pencarian Unik')
            ->assertDontSee('Lembaga Pencarian Unik')
            ->assertViewHas('users', fn ($users) => $users->total() === 1);
    }

    public function test_user_index_paginates_ten_accounts_per_page(): void
    {
        $admin = $this->activeUser(Role::Administrator);

        for ($i = 1; $i <= 11; $i++) {
            $this->activeUser(Role::Donor, 'Donor Halaman '.$i, 'halaman'.$i.'@example.test');
        }

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertViewHas('users', fn ($users) => $users->count() === 10 && $users->total() === 12);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['page' => 2]))
            ->assertOk()
            ->assertViewHas('users', fn ($users) => $users->count() === 2);
    }

    private function donorPayload(): array
    {
        return [
            'nama' => 'Donor Baru',
            'email' => 'donor-baru@example.test',
            'no_hp' => '081234567890',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role' => 'donor',
            'nama_usaha' => 'Dapur Baru',
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Baru No. 1',
            'lat' => '-6.2',
            'lng' => '106.8',
        ];
    }

    private function recipientPayload(): array
    {
        return [
            'nama' => 'Penerima Baru',
            'email' => 'penerima-baru@example.test',
            'no_hp' => '081234567891',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role' => 'penerima',
            'nama_lembaga' => 'Yayasan Baru',
            'jenis_lembaga' => 'Yayasan',
            'alamat' => 'Jl. Baru No. 2',
            'lat' => '-6.2',
            'lng' => '106.8',
        ];
    }

    private function donorProfileFor(User $donor, array $overrides = []): DonorProfile
    {
        return $donor->donorProfile()->create($overrides + [
            'nama_usaha' => 'Usaha '.fake()->unique()->numerify('###'),
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor No. 1',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);
    }

    private function recipientProfileFor(User $recipient, array $overrides = []): RecipientProfile
    {
        return $recipient->recipientProfile()->create($overrides + [
            'nama_lembaga' => 'Lembaga '.fake()->unique()->numerify('###'),
            'jenis_lembaga' => 'Yayasan',
            'alamat' => 'Jl. Penerima No. 1',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);
    }

    private function foodPostFor(DonorProfile $donor, array $overrides = []): FoodPost
    {
        $category = FoodCategory::query()->firstOrCreate(['nama_kategori' => 'Karbohidrat']);

        return FoodPost::create($overrides + [
            'judul' => 'Makanan Uji '.fake()->unique()->numerify('###'),
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

    private function activeUser(Role $role, ?string $name = null, ?string $email = null): User
    {
        return User::factory()->create([
            'nama' => $name ?? fake()->name(),
            'email' => $email ?? $role->value.'-'.fake()->unique()->numerify('###').'@example.test',
            'role' => $role,
            'status_akun' => AccountStatus::Aktif,
        ]);
    }
}
