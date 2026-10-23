<?php

namespace Tests\Unit;

use App\Enums\AccountStatus;
use App\Enums\Role;
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
}
