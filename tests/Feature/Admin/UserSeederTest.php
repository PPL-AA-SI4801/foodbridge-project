<?php

namespace Tests\Feature\Admin;

use App\Enums\AccountStatus;
use App\Models\User;
use Database\Seeders\DemoUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Tests\TestCase;

#[RequiresPhpExtension('pdo_sqlite')]
class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_user_seeder_creates_blocked_accounts_idempotently(): void
    {
        $this->seed(DemoUserSeeder::class);

        $userCount = User::query()->count();

        $this->seed(DemoUserSeeder::class);

        $this->assertSame($userCount, User::query()->count());
        $this->assertDatabaseHas('users', [
            'email' => 'donor-blocked@foodbridge.test',
            'status_akun' => AccountStatus::Diblokir->value,
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'penerima-blocked@foodbridge.test',
            'status_akun' => AccountStatus::Diblokir->value,
        ]);
    }
}
