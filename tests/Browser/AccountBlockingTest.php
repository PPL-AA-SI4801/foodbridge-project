<?php

namespace Tests\Browser;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\InAppNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class AccountBlockingTest extends DuskTestCase
{
    use DatabaseTruncation;

    /**
     * TS-1: admin memblokir akun donor aktif dengan alasan yang valid.
     */
    public function test_ts_1_administrator_blocks_an_active_account_with_a_valid_reason(): void
    {
        $admin = $this->createAdmin();
        $donor = $this->createDonor();

        DB::table('sessions')->insert([
            'id' => 'session-'.$donor->id,
            'user_id' => $donor->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Dusk TS-1',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $this->browse(function (Browser $browser) use ($admin, $donor) {
            $browser->loginAs($admin)
                ->visit('/admin/users')
                ->assertSee('Manajemen Pengguna')
                ->type('#q', $donor->email)
                ->press('Filter')
                ->assertSee($donor->nama)
                ->click("@block-toggle-{$donor->id}")
                ->type("@block-reason-{$donor->id}", 'Terbukti menyalahgunakan data penerima.')
                ->click("@block-user-{$donor->id}")
                ->waitForText('Akun berhasil diblokir.')
                ->assertSee($donor->nama)
                ->assertSee('Sudah diblokir');
        });

        $this->assertSame(AccountStatus::Diblokir, $donor->fresh()->status_akun);

        $notification = InAppNotification::query()
            ->where('user_id', $donor->id)
            ->sole();

        $this->assertSame('Akun Anda Diblokir', $notification->judul);
        $this->assertStringContainsString(
            'Terbukti menyalahgunakan data penerima.',
            $notification->pesan,
        );

        $this->assertDatabaseMissing('sessions', ['id' => 'session-'.$donor->id]);

        $this->browse(function (Browser $browser) use ($donor) {
            $browser->loginAs($donor->fresh())
                ->visit('/dashboard/donor')
                ->waitForLocation('/login')
                ->assertSee('telah diblokir oleh administrator')
                ->assertSee('Terbukti menyalahgunakan data penerima.');
        });
    }

    /**
     * TS-2: admin mencoba memblokir tanpa alasan / alasan terlalu pendek.
     */
    public function test_ts_2_blocking_without_a_valid_reason_is_rejected(): void
    {
        $admin = $this->createAdmin();
        $donor = $this->createDonor();

        $this->browse(function (Browser $browser) use ($admin, $donor) {
            // Tanpa alasan sama sekali.
            $browser->loginAs($admin)
                ->visit('/admin/users')
                ->assertSee('Manajemen Pengguna')
                ->click("@block-toggle-{$donor->id}")
                ->click("@block-user-{$donor->id}")
                ->waitFor('@block-validation-error')
                ->assertSeeIn('@block-validation-error', 'Alasan pemblokiran wajib diisi.');

            // Alasan kurang dari 10 karakter.
            $browser->click("@block-toggle-{$donor->id}")
                ->type("@block-reason-{$donor->id}", 'nakal')
                ->click("@block-user-{$donor->id}")
                ->waitFor('@block-validation-error')
                ->assertSeeIn('@block-validation-error', 'minimal 10 karakter');
        });

        // Status tetap aktif dan tidak ada notifikasi baru.
        $this->assertSame(AccountStatus::Aktif, $donor->fresh()->status_akun);
        $this->assertDatabaseMissing('notifications', ['user_id' => $donor->id]);

        // Akun masih bisa login normal.
        $this->browse(function (Browser $browser) use ($donor) {
            $browser->loginAs($donor->fresh())
                ->visit('/dashboard/donor')
                ->assertPathIs('/dashboard/donor')
                ->assertDontSee('telah diblokir');
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
