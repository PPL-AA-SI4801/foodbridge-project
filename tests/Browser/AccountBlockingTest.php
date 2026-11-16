<?php

namespace Tests\Browser;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AccountBlockingTest extends DuskTestCase
{
    use DatabaseTruncation;

    public function test_administrator_can_block_an_active_account_and_it_cannot_sign_in(): void
    {
        $admin = $this->createAdmin();
        $donor = $this->createDonor();

        $this->browse(function (Browser $browser) use ($admin, $donor) {
            $browser->loginAs($admin)
                ->visit('/admin/users')
                ->assertSee('Manajemen Pengguna')
                ->assertSee($donor->nama)
                ->click("@block-user-{$donor->id}")
                ->acceptDialog()
                ->waitForText('Akun berhasil diblokir.')
                ->assertSee($donor->nama)
                ->assertSee('Sudah diblokir');
        });

        $this->assertSame(AccountStatus::Diblokir, $donor->fresh()->status_akun);

        $this->browse(function (Browser $browser) use ($donor) {
            $browser->loginAs($donor->fresh())
                ->visit('/dashboard/donor')
                ->waitForLocation('/login')
                ->assertSee('Akun Anda telah diblokir.');
        });
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => Role::Administrator,
            'status_akun' => AccountStatus::Aktif,
        ]);
    }

    private function createDonor(): User
    {
        $donor = User::factory()->create([
            'role' => Role::Donor,
            'status_akun' => AccountStatus::Aktif,
        ]);

        $donor->donorProfile()->create([
            'nama_usaha' => 'Dapur Dusk',
            'jenis_usaha' => 'Restoran',
            'alamat' => 'Jl. Dusk No. 1, Jakarta',
            'lat' => -6.2,
            'lng' => 106.8,
        ]);

        return $donor;
    }
}
