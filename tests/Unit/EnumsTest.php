<?php

namespace Tests\Unit;

use App\Enums\AccountStatus;
use App\Enums\FoodPostStatus;
use App\Enums\FoodRequestStatus;
use App\Enums\Role;
use App\Enums\UrgencyLevel;
use App\Enums\VerificationStatus;
use App\Models\FoodRequest;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class EnumsTest extends TestCase
{
    public function test_role_values_match_the_documented_erd_vocabulary(): void
    {
        $this->assertSame(
            ['donor', 'penerima', 'administrator'],
            array_column(Role::cases(), 'value'),
        );
    }

    public function test_account_status_values_match_the_documented_erd_vocabulary(): void
    {
        $this->assertSame(
            ['menunggu', 'aktif', 'ditolak', 'diblokir'],
            array_column(AccountStatus::cases(), 'value'),
        );
    }

    public function test_verification_status_values_match_the_documented_erd_vocabulary(): void
    {
        $this->assertSame(
            ['menunggu', 'disetujui', 'ditolak'],
            array_column(VerificationStatus::cases(), 'value'),
        );
    }

    public function test_food_post_status_values_match_the_documented_erd_vocabulary(): void
    {
        $this->assertSame(
            ['tersedia', 'habis', 'kedaluwarsa'],
            array_column(FoodPostStatus::cases(), 'value'),
        );
    }

    public function test_user_casts_role_and_account_status(): void
    {
        $user = new User([
            'role' => 'donor',
            'status_akun' => 'menunggu',
        ]);

        $this->assertSame(Role::Donor, $user->role);
        $this->assertSame(AccountStatus::Menunggu, $user->status_akun);
        $this->assertFalse($user->isActive());
        $this->assertTrue($user->hasRole(Role::Donor));
        $this->assertTrue($user->hasRole(Role::Administrator, Role::Donor));
        $this->assertFalse($user->hasRole(Role::Administrator));
    }

    public function test_only_aktif_is_an_active_status(): void
    {
        foreach (AccountStatus::cases() as $status) {
            $this->assertSame($status === AccountStatus::Aktif, $status->isActive());
        }
    }

    public function test_each_inactive_status_has_its_own_indonesian_refusal_message(): void
    {
        $this->assertNull(AccountStatus::Aktif->refusalMessage());
        $this->assertSame(
            'Akun Anda sedang menunggu verifikasi administrator.',
            AccountStatus::Menunggu->refusalMessage(),
        );
        $this->assertSame(
            'Pendaftaran Anda ditolak oleh administrator.',
            AccountStatus::Ditolak->refusalMessage(),
        );
        $this->assertSame(
            'Akun Anda telah diblokir.',
            AccountStatus::Diblokir->refusalMessage(),
        );
    }

    public function test_food_request_status_and_urgency_enums_and_model_casts(): void
    {
        $this->assertSame(
            ['open', 'in_progress', 'fulfilled', 'expired', 'cancelled'],
            array_column(FoodRequestStatus::cases(), 'value'),
        );

        $this->assertSame(
            ['normal', 'mendesak', 'darurat'],
            array_column(UrgencyLevel::cases(), 'value'),
        );

        $request = new FoodRequest([
            'judul' => 'Nasi Kotak',
            'jumlah_porsi' => 25,
            'urgency_level' => 'darurat',
            'status' => 'open',
            'metode_penerimaan' => 'diantar_kurir',
        ]);

        $this->assertSame(FoodRequestStatus::Open, $request->status);
        $this->assertTrue($request->status->isOpen());
        $this->assertTrue($request->status->isActive());
        $this->assertSame(UrgencyLevel::Darurat, $request->urgency_level);
        $this->assertSame('Diantar / Kurir', $request->metodePenerimaanLabel());
        $this->assertSame(3, FoodRequest::MAX_OPEN_REQUESTS);
    }
}
