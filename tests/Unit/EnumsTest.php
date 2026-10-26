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

    public function test_each_inactive_status_has_its_own_refusal_message(): void
    {
        $this->assertNull(AccountStatus::Aktif->refusalMessage());

        $messages = array_map(
            fn (AccountStatus $status) => $status->refusalMessage(),
            [AccountStatus::Menunggu, AccountStatus::Ditolak, AccountStatus::Diblokir],
        );

        $this->assertNotContains(null, $messages);
        $this->assertCount(3, array_unique($messages));
    }

    public function test_inactive_status_messages_are_in_indonesian(): void
    {
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
}
