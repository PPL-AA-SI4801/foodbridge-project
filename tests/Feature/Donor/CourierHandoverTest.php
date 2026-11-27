<?php

namespace Tests\Feature\Donor;

use App\Enums\FoodPostStatus;
use App\Enums\NotificationType;
use App\Enums\Role;
use App\Models\Claim;
use App\Models\CourierDetail;
use App\Models\DonorProfile;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\InAppNotification;
use App\Models\RecipientProfile;
use App\Models\User;
use App\Services\ClaimHandoverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use RuntimeException;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class CourierHandoverTest extends TestCase
{
    use RefreshDatabase;

    public function test_tc23_01_donor_mengunggah_foto_lalu_menyerahkan_makanan(): void
    {
        Storage::fake('public');

        [$recipient, $recipientUser] = $this->recipientProfile();
        [$donor, $donorUser] = $this->donorProfile();
        $post = $this->foodPost($donor, $this->foodCategory());
        $claim = $this->claim($recipient, $post, [
            'status' => 'approved',
            'metode_pengambilan' => Claim::METHOD_KURIR_ONLINE,
            'approved_at' => now(),
        ]);
        $this->courierDetail($claim);

        $this->actingAs($donorUser)
            ->patch(route('donor.claims.complete_courier', $claim), [
                'foto_serah_terima' => UploadedFile::fake()->create('kurir.jpg', 1024, 'image/jpeg'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Makanan sudah diserahkan. Klaim selesai.');

        $this->assertSame('completed', $claim->fresh()->status);
        $this->assertNotNull($claim->fresh()->completed_at);
        $this->assertNotNull($claim->fresh()->courierDetail->foto_serah_terima);
        Storage::disk('public')->assertExists($claim->fresh()->courierDetail->foto_serah_terima);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $recipientUser->id,
            'judul' => 'Makanan Telah Diserahkan',
            'tipe' => NotificationType::Claim->value,
        ]);
    }

    public function test_tc23_02_status_selesai_tampil_di_donor_dan_penerima(): void
    {
        Storage::fake('public');

        [$recipient, $recipientUser] = $this->recipientProfile();
        [$donor, $donorUser] = $this->donorProfile();
        $post = $this->foodPost($donor, $this->foodCategory());
        $claim = $this->claim($recipient, $post, [
            'status' => 'approved',
            'metode_pengambilan' => Claim::METHOD_KURIR_ONLINE,
            'approved_at' => now(),
        ]);
        $courier = $this->courierDetail($claim, ['nama_pengemudi' => 'Budi Santoso']);

        $this->actingAs($donorUser)
            ->patch(route('donor.claims.complete_courier', $claim), [
                'foto_serah_terima' => UploadedFile::fake()->create('kurir.jpg', 500, 'image/jpeg'),
            ])
            ->assertRedirect();

        $fotoPath = $claim->fresh()->courierDetail->foto_serah_terima;

        // Donor: badge waktu + foto tampil, form hilang.
        $this->actingAs($donorUser)
            ->get(route('donor.claims.index', ['claim' => $claim->id]))
            ->assertOk()
            ->assertSee('Diserahkan')
            ->assertSee('Budi Santoso')
            ->assertSee($fotoPath)
            ->assertDontSee('courier-handover-form-'.$claim->id);

        // Penerima: status selesai + waktu tampil, foto tidak tampil.
        $this->actingAs($recipientUser)
            ->get(route('recipient.claims.index', ['claim' => $claim->id]))
            ->assertOk()
            ->assertSee('Makanan sudah diserahkan oleh donor.')
            ->assertDontSee($fotoPath);

        // Notifikasi mengarah ke Klaim Saya.
        $notification = InAppNotification::query()
            ->where('user_id', $recipientUser->id)
            ->where('judul', 'Makanan Telah Diserahkan')
            ->firstOrFail();

        $this->actingAs($recipientUser)
            ->get(route('notifications.open', $notification))
            ->assertRedirect(route('recipient.claims.index'));

        $this->assertSame($courier->kode_verifikasi_kurir, $claim->fresh()->courierDetail->kode_verifikasi_kurir);
    }

    public function test_tc23_03_foto_kosong_ditolak(): void
    {
        [$recipient] = $this->recipientProfile();
        [$donor, $donorUser] = $this->donorProfile();
        $post = $this->foodPost($donor, $this->foodCategory());
        $claim = $this->claim($recipient, $post, [
            'status' => 'approved',
            'metode_pengambilan' => Claim::METHOD_KURIR_ONLINE,
            'approved_at' => now(),
        ]);
        $this->courierDetail($claim);

        $this->actingAs($donorUser)
            ->from(route('donor.claims.index'))
            ->patch(route('donor.claims.complete_courier', $claim), [])
            ->assertRedirect()
            ->assertSessionHasErrors(['foto_serah_terima' => 'Foto kurir wajib diunggah.']);

        $this->assertSame('approved', $claim->fresh()->status);
        $this->assertNull($claim->fresh()->completed_at);
        $this->assertNull($claim->fresh()->courierDetail->foto_serah_terima);
        $this->assertDatabaseMissing('notifications', [
            'judul' => 'Makanan Telah Diserahkan',
        ]);
    }

    public function test_tc23_04_format_foto_tidak_valid_ditolak(): void
    {
        [$recipient] = $this->recipientProfile();
        [$donor, $donorUser] = $this->donorProfile();
        $post = $this->foodPost($donor, $this->foodCategory());
        $claim = $this->claim($recipient, $post, [
            'status' => 'approved',
            'metode_pengambilan' => Claim::METHOD_KURIR_ONLINE,
            'approved_at' => now(),
        ]);
        $this->courierDetail($claim);

        foreach ([
            UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('bukti.exe', 100, 'application/x-msdownload'),
        ] as $file) {
            $this->actingAs($donorUser)
                ->from(route('donor.claims.index'))
                ->patch(route('donor.claims.complete_courier', $claim), [
                    'foto_serah_terima' => $file,
                ])
                ->assertRedirect()
                ->assertSessionHasErrors(['foto_serah_terima' => 'Foto harus berformat JPG, JPEG, PNG, atau WEBP.']);
        }

        $this->assertSame('approved', $claim->fresh()->status);
        $this->assertNull($claim->fresh()->courierDetail->foto_serah_terima);
        $this->assertDatabaseMissing('notifications', [
            'judul' => 'Makanan Telah Diserahkan',
        ]);
    }

    public function test_tc23_05_foto_melebihi_2mb_ditolak(): void
    {
        [$recipient] = $this->recipientProfile();
        [$donor, $donorUser] = $this->donorProfile();
        $post = $this->foodPost($donor, $this->foodCategory());
        $claim = $this->claim($recipient, $post, [
            'status' => 'approved',
            'metode_pengambilan' => Claim::METHOD_KURIR_ONLINE,
            'approved_at' => now(),
        ]);
        $this->courierDetail($claim);

        $this->actingAs($donorUser)
            ->from(route('donor.claims.index'))
            ->patch(route('donor.claims.complete_courier', $claim), [
                'foto_serah_terima' => UploadedFile::fake()->create('besar.jpg', 3072, 'image/jpeg'),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors(['foto_serah_terima' => 'Ukuran foto maksimal 2 MB.']);

        $this->assertSame('approved', $claim->fresh()->status);
        $this->assertNull($claim->fresh()->courierDetail->foto_serah_terima);
        $this->assertDatabaseMissing('notifications', [
            'judul' => 'Makanan Telah Diserahkan',
        ]);
    }

    public function test_tc23_06_penyelesaian_berulang_ditolak_tanpa_notifikasi_ganda(): void
    {
        Storage::fake('public');

        [$recipient, $recipientUser] = $this->recipientProfile();
        [$donor, $donorUser] = $this->donorProfile();
        $post = $this->foodPost($donor, $this->foodCategory());
        $claim = $this->claim($recipient, $post, [
            'status' => 'approved',
            'metode_pengambilan' => Claim::METHOD_KURIR_ONLINE,
            'approved_at' => now(),
        ]);
        $this->courierDetail($claim);

        $this->actingAs($donorUser)
            ->patch(route('donor.claims.complete_courier', $claim), [
                'foto_serah_terima' => UploadedFile::fake()->create('kurir.jpg', 500, 'image/jpeg'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $fotoPath = $claim->fresh()->courierDetail->foto_serah_terima;
        $completedAt = $claim->fresh()->completed_at;

        // Aksi ulang pada klaim yang sudah selesai.
        $this->actingAs($donorUser)
            ->from(route('donor.claims.index'))
            ->patch(route('donor.claims.complete_courier', $claim->fresh()), [
                'foto_serah_terima' => UploadedFile::fake()->create('kurir2.jpg', 500, 'image/jpeg'),
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Hanya klaim yang disetujui yang dapat diselesaikan.');

        $this->assertSame($fotoPath, $claim->fresh()->courierDetail->foto_serah_terima);
        $this->assertEquals($completedAt, $claim->fresh()->completed_at);
        $this->assertSame(1, InAppNotification::query()
            ->where('user_id', $recipientUser->id)
            ->where('judul', 'Makanan Telah Diserahkan')
            ->count());
    }

    public function test_tc23_07_donor_lain_tidak_dapat_menyelesaikan_atau_melihat(): void
    {
        Storage::fake('public');

        [$recipient] = $this->recipientProfile();
        [$donor] = $this->donorProfile();
        [$otherDonor, $otherDonorUser] = $this->donorProfile();
        $post = $this->foodPost($donor, $this->foodCategory());
        $claim = $this->claim($recipient, $post, [
            'status' => 'approved',
            'metode_pengambilan' => Claim::METHOD_KURIR_ONLINE,
            'approved_at' => now(),
        ]);
        $this->courierDetail($claim, ['nama_pengemudi' => 'Budi Santoso Rahasia']);

        // Tamu: redirect ke login.
        $this->patch(route('donor.claims.complete_courier', $claim), [
            'foto_serah_terima' => UploadedFile::fake()->create('kurir.jpg', 500, 'image/jpeg'),
        ])->assertRedirect(route('login'));

        // Donor lain: 403.
        $this->actingAs($otherDonorUser)
            ->patch(route('donor.claims.complete_courier', $claim), [
                'foto_serah_terima' => UploadedFile::fake()->create('kurir.jpg', 500, 'image/jpeg'),
            ])
            ->assertForbidden();

        // Donor lain tidak melihat data pengemudi maupun foto.
        $this->actingAs($otherDonorUser)
            ->get(route('donor.claims.index', ['claim' => $claim->id]))
            ->assertOk()
            ->assertDontSee('Budi Santoso Rahasia');

        $this->assertSame('approved', $claim->fresh()->status);
        $this->assertNull($claim->fresh()->courierDetail->foto_serah_terima);
        $this->assertDatabaseMissing('notifications', [
            'judul' => 'Makanan Telah Diserahkan',
        ]);
    }

    public function test_tc23_08_klaim_belum_approved_ditolak(): void
    {
        [$recipient] = $this->recipientProfile();
        [$donor, $donorUser] = $this->donorProfile();
        $post = $this->foodPost($donor, $this->foodCategory());

        foreach (['pending', 'rejected'] as $status) {
            $claim = $this->claim($recipient, $post, [
                'status' => $status,
                'metode_pengambilan' => Claim::METHOD_KURIR_ONLINE,
            ]);
            $this->courierDetail($claim);

            $this->actingAs($donorUser)
                ->from(route('donor.claims.index'))
                ->patch(route('donor.claims.complete_courier', $claim), [
                    'foto_serah_terima' => UploadedFile::fake()->create('kurir.jpg', 500, 'image/jpeg'),
                ])
                ->assertRedirect()
                ->assertSessionHas('error', 'Hanya klaim yang disetujui yang dapat diselesaikan.');

            $this->assertSame($status, $claim->fresh()->status);
        }

        $this->assertDatabaseMissing('notifications', [
            'judul' => 'Makanan Telah Diserahkan',
        ]);
    }

    public function test_tc23_09_metode_tanpa_kurir_atau_tanpa_data_pengemudi_ditolak(): void
    {
        [$recipient] = $this->recipientProfile();
        [$donor, $donorUser] = $this->donorProfile();
        $post = $this->foodPost($donor, $this->foodCategory());

        // Ambil-sendiri: wajib lewat jalur kode, bukan foto.
        $mandiri = $this->claim($recipient, $post, [
            'status' => 'approved',
            'metode_pengambilan' => Claim::METHOD_AMBIL_SENDIRI,
            'kode_verifikasi' => '482913',
            'approved_at' => now(),
        ]);

        $this->actingAs($donorUser)
            ->from(route('donor.claims.index'))
            ->patch(route('donor.claims.complete_courier', $mandiri), [
                'foto_serah_terima' => UploadedFile::fake()->create('kurir.jpg', 500, 'image/jpeg'),
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Penyelesaian dengan foto hanya untuk metode Kurir Online.');

        // Kurir online tanpa record courier_details.
        $tanpaDriver = $this->claim($recipient, $post, [
            'status' => 'approved',
            'metode_pengambilan' => Claim::METHOD_KURIR_ONLINE,
            'approved_at' => now(),
        ]);

        $this->actingAs($donorUser)
            ->from(route('donor.claims.index'))
            ->patch(route('donor.claims.complete_courier', $tanpaDriver), [
                'foto_serah_terima' => UploadedFile::fake()->create('kurir.jpg', 500, 'image/jpeg'),
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Data pengemudi belum tersedia.');

        $this->assertSame('approved', $mandiri->fresh()->status);
        $this->assertSame('approved', $tanpaDriver->fresh()->status);
        $this->assertNull($tanpaDriver->fresh()->completed_at);
        $this->assertDatabaseMissing('notifications', [
            'judul' => 'Makanan Telah Diserahkan',
        ]);
    }

    public function test_tc23_10_kegagalan_penyimpanan_membatalkan_seluruh_proses(): void
    {
        [$recipient] = $this->recipientProfile();
        [$donor, $donorUser] = $this->donorProfile();
        $post = $this->foodPost($donor, $this->foodCategory());
        $claim = $this->claim($recipient, $post, [
            'status' => 'approved',
            'metode_pengambilan' => Claim::METHOD_KURIR_ONLINE,
            'approved_at' => now(),
        ]);
        $this->courierDetail($claim);

        // Simulasi disk penuh: penyimpanan foto gagal sebelum ada data tersimpan.
        $foto = Mockery::mock(UploadedFile::class);
        $foto->shouldReceive('store')->once()->andThrow(new RuntimeException('disk penuh'));

        try {
            app(ClaimHandoverService::class)->completeCourier($claim->fresh(), $foto, $donorUser);
            $this->fail('Penyimpanan yang gagal seharusnya melempar exception.');
        } catch (RuntimeException $e) {
            $this->assertSame('disk penuh', $e->getMessage());
        }

        $this->assertSame('approved', $claim->fresh()->status);
        $this->assertNull($claim->fresh()->completed_at);
        $this->assertNull($claim->fresh()->courierDetail->foto_serah_terima);
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
            'judul' => 'Nasi Box Kurir '.uniqid(),
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

    private function courierDetail(Claim $claim, array $overrides = []): CourierDetail
    {
        return CourierDetail::create(array_merge([
            'claim_id' => $claim->id,
            'platform' => 'Gojek',
            'nama_pengemudi' => 'Budi Santoso',
            'no_kendaraan' => 'D 1234 ABC',
            'kode_verifikasi_kurir' => 'GJ-48213',
            'foto_bukti' => 'courier_proofs/bukti.png',
            'created_at' => now(),
        ], $overrides));
    }
}
