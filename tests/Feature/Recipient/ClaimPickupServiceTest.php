<?php

namespace Tests\Feature\Recipient;

use App\Enums\FoodPostStatus;
use App\Enums\NotificationType;
use App\Enums\Role;
use App\Http\Requests\Recipient\ChoosePickupMethodRequest;
use App\Models\Claim;
use App\Models\CourierDetail;
use App\Models\DonorProfile;
use App\Models\FoodCategory;
use App\Models\FoodPost;
use App\Models\RecipientProfile;
use App\Models\User;
use App\Services\ClaimPickupService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class ClaimPickupServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClaimPickupService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ClaimPickupService;
        Storage::fake('public');
    }

    public function test_recipient_can_choose_mandiri_pickup_method_successfully(): void
    {
        [$recipient] = $this->recipientProfile();
        $donor = $this->donorProfile();
        $category = $this->foodCategory();
        $post = $this->foodPost($donor, $category);
        $claim = $this->claim($recipient, $post, ['status' => 'approved']);

        $result = $this->service->choosePickupMethod(
            $claim,
            ['metode_pengambilan' => Claim::METHOD_AMBIL_SENDIRI],
            null,
            $recipient->user
        );

        $this->assertSame(Claim::METHOD_AMBIL_SENDIRI, $result->fresh()->metode_pengambilan);
        $this->assertDatabaseHas('claims', [
            'id' => $claim->id,
            'metode_pengambilan' => Claim::METHOD_AMBIL_SENDIRI,
        ]);
        $this->assertDatabaseMissing('courier_details', [
            'claim_id' => $claim->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $donor->user_id,
            'tipe' => NotificationType::Claim->value,
            'judul' => 'Metode Pengambilan Ditentukan',
        ]);
    }

    public function test_recipient_can_choose_kurir_pickup_method_with_valid_details_and_photo(): void
    {
        [$recipient] = $this->recipientProfile();
        $donor = $this->donorProfile();
        $category = $this->foodCategory();
        $post = $this->foodPost($donor, $category);
        $claim = $this->claim($recipient, $post, ['status' => 'approved']);

        $photo = UploadedFile::fake()->create('pesanan.jpg', 1024, 'image/jpeg');

        $result = $this->service->choosePickupMethod(
            $claim,
            [
                'metode_pengambilan' => Claim::METHOD_KURIR,
                'platform' => CourierDetail::PLATFORM_GOJEK,
                'nama_pengemudi' => 'Budi Santoso',
                'no_kendaraan' => 'D 1234 ABC',
                'kode_verifikasi_kurir' => 'GJ-48213',
            ],
            $photo,
            $recipient->user
        );

        $this->assertSame(Claim::METHOD_KURIR, $result->fresh()->metode_pengambilan);

        $courier = CourierDetail::where('claim_id', $claim->id)->first();
        $this->assertNotNull($courier);
        $this->assertSame('Gojek', $courier->platform);
        $this->assertSame('Budi Santoso', $courier->nama_pengemudi);
        $this->assertSame('D 1234 ABC', $courier->no_kendaraan);
        $this->assertSame('GJ-48213', $courier->kode_verifikasi_kurir);
        Storage::disk('public')->assertExists($courier->foto_bukti);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $donor->user_id,
            'tipe' => NotificationType::Claim->value,
            'judul' => 'Metode Pengambilan Ditentukan',
        ]);
    }

    public function test_cannot_choose_pickup_method_if_claim_is_not_approved(): void
    {
        [$recipient] = $this->recipientProfile();
        $donor = $this->donorProfile();
        $category = $this->foodCategory();
        $post = $this->foodPost($donor, $category);
        $claim = $this->claim($recipient, $post, ['status' => 'pending']);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Klaim belum disetujui.');

        $this->service->choosePickupMethod(
            $claim,
            ['metode_pengambilan' => Claim::METHOD_AMBIL_SENDIRI],
            null,
            $recipient->user
        );
    }

    public function test_cannot_choose_pickup_method_if_method_already_chosen(): void
    {
        [$recipient] = $this->recipientProfile();
        $donor = $this->donorProfile();
        $category = $this->foodCategory();
        $post = $this->foodPost($donor, $category);
        $claim = $this->claim($recipient, $post, [
            'status' => 'approved',
            'metode_pengambilan' => Claim::METHOD_AMBIL_SENDIRI,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Metode pengambilan sudah dipilih.');

        $this->service->choosePickupMethod(
            $claim,
            ['metode_pengambilan' => Claim::METHOD_KURIR],
            null,
            $recipient->user
        );
    }

    public function test_cannot_choose_pickup_method_if_method_is_invalid(): void
    {
        [$recipient] = $this->recipientProfile();
        $donor = $this->donorProfile();
        $category = $this->foodCategory();
        $post = $this->foodPost($donor, $category);
        $claim = $this->claim($recipient, $post, ['status' => 'approved']);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Metode pengambilan tidak valid.');

        $this->service->choosePickupMethod(
            $claim,
            ['metode_pengambilan' => 'ojek'],
            null,
            $recipient->user
        );
    }

    public function test_cannot_choose_pickup_method_for_another_recipients_claim(): void
    {
        [$recipient] = $this->recipientProfile();
        [$otherRecipient] = $this->recipientProfile();
        $donor = $this->donorProfile();
        $category = $this->foodCategory();
        $post = $this->foodPost($donor, $category);
        $claim = $this->claim($recipient, $post, ['status' => 'approved']);

        $this->expectException(HttpException::class);

        $this->service->choosePickupMethod(
            $claim,
            ['metode_pengambilan' => Claim::METHOD_AMBIL_SENDIRI],
            null,
            $otherRecipient->user
        );
    }

    public function test_cannot_choose_pickup_method_if_food_post_is_expired(): void
    {
        [$recipient] = $this->recipientProfile();
        $donor = $this->donorProfile();
        $category = $this->foodCategory();
        $post = $this->foodPost($donor, $category, ['batas_konsumsi' => now()->subHour()]);
        $claim = $this->claim($recipient, $post, ['status' => 'approved']);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Makanan sudah melewati batas konsumsi.');

        $this->service->choosePickupMethod(
            $claim,
            ['metode_pengambilan' => Claim::METHOD_AMBIL_SENDIRI],
            null,
            $recipient->user
        );
    }

    public function test_choose_pickup_method_request_authorization(): void
    {
        [$recipient] = $this->recipientProfile();
        $donor = User::factory()->create(['role' => Role::Donor, 'status_akun' => 'aktif']);
        $admin = User::factory()->create(['role' => Role::Administrator, 'status_akun' => 'aktif']);

        $request = new ChoosePickupMethodRequest;

        $request->setUserResolver(fn () => $donor);
        $this->assertFalse($request->authorize());

        $request->setUserResolver(fn () => $admin);
        $this->assertFalse($request->authorize());

        $request->setUserResolver(fn () => $recipient->user);
        $this->assertTrue($request->authorize());
    }

    public function test_request_validation_for_kurir_missing_fields_and_invalid_photo(): void
    {
        $request = new ChoosePickupMethodRequest;

        // 1. Invalid method 'ojek'
        $validator = Validator::make(
            ['metode_pengambilan' => 'ojek'],
            $request->rules(),
            $request->messages(),
            $request->attributes()
        );
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('metode_pengambilan', $validator->errors()->toArray());

        // 2. Kurir with missing fields
        $validator = Validator::make(
            ['metode_pengambilan' => 'kurir'],
            $request->rules(),
            $request->messages(),
            $request->attributes()
        );
        $this->assertTrue($validator->fails());
        $errors = $validator->errors();
        $this->assertTrue($errors->has('platform'));
        $this->assertTrue($errors->has('nama_pengemudi'));
        $this->assertTrue($errors->has('no_kendaraan'));
        $this->assertTrue($errors->has('kode_verifikasi_kurir'));
        $this->assertTrue($errors->has('foto_bukti'));

        // 3. Photo format invalid (PDF)
        $fakePdf = UploadedFile::fake()->create('dokumen.pdf', 500, 'application/pdf');
        $validator = Validator::make(
            [
                'metode_pengambilan' => 'kurir',
                'platform' => 'Gojek',
                'nama_pengemudi' => 'Budi',
                'no_kendaraan' => 'D 1234 ABC',
                'kode_verifikasi_kurir' => 'GJ-123',
                'foto_bukti' => $fakePdf,
            ],
            $request->rules(),
            $request->messages(),
            $request->attributes()
        );
        $this->assertTrue($validator->fails());
        $this->assertSame('Foto harus berformat JPG, JPEG, PNG, atau WEBP.', $validator->errors()->first('foto_bukti'));

        // 4. Photo size exceeds 2 MB (3 MB)
        $fakeLargeImg = UploadedFile::fake()->create('bukti.png', 3072, 'image/png');
        $validator = Validator::make(
            [
                'metode_pengambilan' => 'kurir',
                'platform' => 'Grab',
                'nama_pengemudi' => 'Budi',
                'no_kendaraan' => 'D 1234 ABC',
                'kode_verifikasi_kurir' => 'GB-123',
                'foto_bukti' => $fakeLargeImg,
            ],
            $request->rules(),
            $request->messages(),
            $request->attributes()
        );
        $this->assertTrue($validator->fails());
        $this->assertSame('Ukuran foto maksimal 2 MB.', $validator->errors()->first('foto_bukti'));
    }

    public function test_http_penerima_can_select_mandiri_method_via_post(): void
    {
        [$recipient] = $this->recipientProfile();
        $donor = $this->donorProfile();
        $category = $this->foodCategory();
        $post = $this->foodPost($donor, $category);
        $claim = $this->claim($recipient, $post, ['status' => 'approved']);

        $response = $this->actingAs($recipient->user)->post(
            route('recipient.claims.pickup-method.store', $claim),
            ['metode_pengambilan' => 'ambil_sendiri']
        );

        $response->assertRedirect(route('recipient.claims.index', ['claim' => $claim->id]))
            ->assertSessionHas('success', 'Metode pengambilan berhasil disimpan.');

        $this->assertSame('ambil_sendiri', $claim->fresh()->metode_pengambilan);
    }

    public function test_http_penerima_can_select_kurir_method_via_post(): void
    {
        [$recipient] = $this->recipientProfile();
        $donor = $this->donorProfile();
        $category = $this->foodCategory();
        $post = $this->foodPost($donor, $category);
        $claim = $this->claim($recipient, $post, ['status' => 'approved']);

        $photo = UploadedFile::fake()->create('pesanan.jpg', 800, 'image/jpeg');

        $response = $this->actingAs($recipient->user)->post(
            route('recipient.claims.pickup-method.store', $claim),
            [
                'metode_pengambilan' => 'kurir_online',
                'platform' => 'Gojek',
                'nama_pengemudi' => 'Budi Santoso',
                'no_kendaraan' => 'D 1234 ABC',
                'kode_verifikasi_kurir' => 'GJ-48213',
                'foto_bukti' => $photo,
            ]
        );

        $response->assertRedirect(route('recipient.claims.index', ['claim' => $claim->id]))
            ->assertSessionHas('success', 'Metode pengambilan berhasil disimpan.');

        $this->assertSame('kurir_online', $claim->fresh()->metode_pengambilan);
        $this->assertDatabaseHas('courier_details', [
            'claim_id' => $claim->id,
            'platform' => 'Gojek',
            'nama_pengemudi' => 'Budi Santoso',
        ]);
    }

    public function test_http_penerima_validation_errors_redirect_back_with_errors(): void
    {
        [$recipient] = $this->recipientProfile();
        $donor = $this->donorProfile();
        $category = $this->foodCategory();
        $post = $this->foodPost($donor, $category);
        $claim = $this->claim($recipient, $post, ['status' => 'approved']);

        $response = $this->actingAs($recipient->user)->post(
            route('recipient.claims.pickup-method.store', $claim),
            [
                'claim_id' => $claim->id,
                'metode_pengambilan' => 'kurir_online',
            ]
        );

        $response->assertSessionHasErrors([
            'platform',
            'nama_pengemudi',
            'no_kendaraan',
            'kode_verifikasi_kurir',
            'foto_bukti',
        ]);
    }

    public function test_http_donor_sees_courier_details_and_photo_on_claims_page(): void
    {
        [$recipient] = $this->recipientProfile();
        $donor = $this->donorProfile();
        $category = $this->foodCategory();
        $post = $this->foodPost($donor, $category);
        $claim = $this->claim($recipient, $post, [
            'status' => 'approved',
            'metode_pengambilan' => 'kurir_online',
        ]);

        CourierDetail::create([
            'claim_id' => $claim->id,
            'platform' => 'Grab',
            'nama_pengemudi' => 'Ahmad Kurir',
            'no_kendaraan' => 'B 9988 XYZ',
            'kode_verifikasi_kurir' => 'GB-999',
            'foto_bukti' => 'seed/placeholder.webp',
            'created_at' => now(),
        ]);

        $claimAmbilSendiri = $this->claim($recipient, $post, [
            'status' => 'approved',
            'metode_pengambilan' => 'ambil_sendiri',
        ]);

        $response = $this->actingAs($donor->user)->get(route('donor.claims.index'));

        $response->assertOk()
            ->assertSee('Kurir Online')
            ->assertSee('Ahmad Kurir')
            ->assertSee('B 9988 XYZ')
            ->assertSee('GB-999')
            ->assertSee('Pengambilan: Ambil Sendiri');
    }

    public function test_http_unauthenticated_user_cannot_access_endpoint(): void
    {
        [$recipient] = $this->recipientProfile();
        $donor = $this->donorProfile();
        $category = $this->foodCategory();
        $post = $this->foodPost($donor, $category);
        $claim = $this->claim($recipient, $post, ['status' => 'approved']);

        $response = $this->post(route('recipient.claims.pickup-method.store', $claim), [
            'metode_pengambilan' => 'ambil_sendiri',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_http_non_recipient_cannot_access_endpoint(): void
    {
        [$recipient] = $this->recipientProfile();
        $donor = $this->donorProfile();
        $category = $this->foodCategory();
        $post = $this->foodPost($donor, $category);
        $claim = $this->claim($recipient, $post, ['status' => 'approved']);

        $response = $this->actingAs($donor->user)->post(
            route('recipient.claims.pickup-method.store', $claim),
            ['metode_pengambilan' => 'ambil_sendiri']
        );

        $response->assertForbidden();
    }

    public function test_http_recipient_cannot_select_method_for_another_recipients_claim(): void
    {
        [$recipient] = $this->recipientProfile();
        [$otherRecipient] = $this->recipientProfile();
        $donor = $this->donorProfile();
        $category = $this->foodCategory();
        $post = $this->foodPost($donor, $category);
        $claim = $this->claim($recipient, $post, ['status' => 'approved']);

        $response = $this->actingAs($otherRecipient->user)->post(
            route('recipient.claims.pickup-method.store', $claim),
            ['metode_pengambilan' => 'ambil_sendiri']
        );

        $response->assertForbidden();
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

    private function donorProfile(array $overrides = []): DonorProfile
    {
        $user = User::factory()->create([
            'role' => Role::Donor,
            'status_akun' => 'aktif',
        ]);

        return $user->donorProfile()->create(array_merge([
            'nama_usaha' => fake()->unique()->company(),
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Donor No. 1, Bandung',
            'lat' => -6.2,
            'lng' => 106.8,
        ], $overrides));
    }

    private function foodCategory(string $name = 'Karbohidrat'): FoodCategory
    {
        return FoodCategory::create(['nama_kategori' => $name]);
    }

    private function foodPost(DonorProfile $donor, FoodCategory $category, array $overrides = []): FoodPost
    {
        return FoodPost::create(array_merge([
            'judul' => 'Nasi Box',
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
