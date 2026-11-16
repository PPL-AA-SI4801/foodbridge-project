<?php

namespace Tests\Browser;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ProfileManagementTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * TC-4-01: Donor can view their own profile.
     */
    public function test_donor_can_view_own_profile(): void
    {
        $donor = $this->createDonor('donor-lihat@example.com', 'Resto Donor Satu', -6.9175, 107.6191, 'Jl. Merdeka No. 1, Bandung');

        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::where('email', 'donor-lihat@example.com')->firstOrFail())
                ->visit('/profile')
                ->assertSee('Profil Saya')
                ->assertSee('Resto Donor Satu')
                ->assertInputValue('alamat', 'Jl. Merdeka No. 1, Bandung')
                ->assertInputValue('lat', '-6.9175')
                ->assertInputValue('lng', '107.6191');
        });
    }

    /**
     * TC-4-02: Donor can update profile and location via hidden lat/lng inputs.
     */
    public function test_donor_can_update_profile_and_location(): void
    {
        $this->createDonor('donor-ubah@example.com', 'Resto Lama', -6.9, 107.6, 'Jl. Lama No. 1');

        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::where('email', 'donor-ubah@example.com')->firstOrFail())
                ->visit('/profile')
                ->assertSee('Profil Saya')
                ->clear('nama_usaha')
                ->type('nama_usaha', 'Resto Sedap Baru')
                ->clear('alamat')
                ->type('alamat', 'Jl. Asia Afrika No. 10, Bandung');
            $browser->script("document.getElementById('lat').value='-6.9217'; document.getElementById('lng').value='107.6071';");
            $browser->press('@profile-submit')
                ->waitForText('Profil berhasil diperbarui.')
                ->assertPathIs('/profile')
                ->assertSee('Profil berhasil diperbarui.');
        });

        $this->assertDatabaseHas('donor_profiles', [
            'nama_usaha' => 'Resto Sedap Baru',
            'alamat' => 'Jl. Asia Afrika No. 10, Bandung',
        ]);
        $user = User::where('email', 'donor-ubah@example.com')->firstOrFail();
        $profile = $user->donorProfile;
        $this->assertEqualsWithDelta(-6.9217, (float) $profile->lat, 0.0001);
        $this->assertEqualsWithDelta(107.6071, (float) $profile->lng, 0.0001);
    }

    /**
     * TC-4-03: Recipient can update profile and location.
     */
    public function test_recipient_can_update_profile_and_location(): void
    {
        $this->createRecipient('penerima-ubah@example.com', 'Panti Asuhan Harapan');

        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::where('email', 'penerima-ubah@example.com')->firstOrFail())
                ->visit('/profile')
                ->assertSee('Profil Saya')
                ->assertSee('Panti Asuhan Harapan')
                ->clear('alamat')
                ->type('alamat', 'Jl. Baru No. 20, Bandung');
            $browser->script("document.getElementById('lat').value='-6.9147'; document.getElementById('lng').value='107.6098';");
            $browser->press('@profile-submit')
                ->waitForText('Profil berhasil diperbarui.')
                ->assertSee('Profil berhasil diperbarui.');
        });

        $this->assertDatabaseHas('recipient_profiles', [
            'nama_lembaga' => 'Panti Asuhan Harapan',
            'alamat' => 'Jl. Baru No. 20, Bandung',
        ]);
    }

    /**
     * TC-4-04: Invalid profile input is rejected and data stays unchanged.
     */
    public function test_profile_update_is_rejected_for_invalid_input(): void
    {
        $this->createDonor('donor-invalid@example.com', 'Resto Valid', -6.9, 107.6, 'Jl. Valid No. 1');

        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::where('email', 'donor-invalid@example.com')->firstOrFail())
                ->visit('/profile')
                ->clear('nama_usaha');
            $browser->script("document.querySelector('[dusk=profile-form]').setAttribute('novalidate','novalidate'); document.querySelectorAll('[required]').forEach(function (el) { el.removeAttribute('required'); }); document.getElementById('lat').value='95'; document.getElementById('lng').value='200';");
            $browser->press('@profile-submit')
                ->pause(500)
                ->assertPathIs('/profile')
                ->assertSee('nama usaha')
                ->assertSee('latitude')
                ->assertSee('longitude');
        });

        $this->assertDatabaseHas('donor_profiles', [
            'nama_usaha' => 'Resto Valid',
        ]);
        $this->assertDatabaseMissing('donor_profiles', [
            'nama_usaha' => '',
        ]);
    }

    /**
     * TC-4-05: A donor cannot update another donor's profile via injected user_id.
     *
     * ProfileController uses Auth::user() only, so an injected user_id field
     * must be ignored.
     */
    public function test_user_cannot_update_another_users_profile(): void
    {
        $donorA = $this->createDonor('donor-a@example.com', 'Resto A Asli', -6.9, 107.6, 'Jl. A No. 1');
        $donorB = $this->createDonor('donor-b@example.com', 'Resto B Asli', -6.91, 107.61, 'Jl. B No. 2');

        $this->browse(function (Browser $browser) use ($donorB) {
            $browser->loginAs(User::where('email', 'donor-a@example.com')->firstOrFail())
                ->visit('/profile');
            $browser->script("var i=document.createElement('input'); i.type='hidden'; i.name='user_id'; i.value='".$donorB->id."'; document.querySelector('[dusk=profile-form]').appendChild(i);");
            $browser->clear('nama_usaha')
                ->type('nama_usaha', 'Diubah Donor A')
                ->press('@profile-submit')
                ->waitForText('Profil berhasil diperbarui.');
        });

        $this->assertDatabaseHas('donor_profiles', [
            'user_id' => $donorA->id,
            'nama_usaha' => 'Diubah Donor A',
        ]);
        $this->assertDatabaseHas('donor_profiles', [
            'user_id' => $donorB->id,
            'nama_usaha' => 'Resto B Asli',
        ]);
    }

    /**
     * TC-4-06: Inactive account cannot access the profile page.
     *
     * ASSUMPTION DIFFERENCE: real behavior is logout + redirect to /login
     * with the status message (EnsureAccountIsActive), not HTTP 403.
     */
    public function test_inactive_account_cannot_access_profile(): void
    {
        $pending = $this->createDonor('pending-profil@example.com', 'Resto Pending', -6.9, 107.6, 'Jl. Pending No. 1', 'menunggu');

        $this->browse(function (Browser $browser) use ($pending) {
            $browser->loginAs($pending)
                ->visit('/profile')
                ->assertPathIs('/login')
                ->assertSee('menunggu verifikasi');
        });
    }

    private function createDonor(string $email, string $namaUsaha, float $lat, float $lng, string $alamat, string $status = 'aktif'): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'role' => Role::Donor,
            'status_akun' => $status,
        ]);
        $user->donorProfile()->create([
            'nama_usaha' => $namaUsaha,
            'jenis_usaha' => 'Restoran',
            'alamat' => $alamat,
            'lat' => $lat,
            'lng' => $lng,
        ]);

        return $user->refresh();
    }

    private function createRecipient(string $email, string $namaLembaga): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'role' => Role::Penerima,
            'status_akun' => 'aktif',
        ]);
        $user->recipientProfile()->create([
            'nama_lembaga' => $namaLembaga,
            'jenis_lembaga' => 'Panti Asuhan',
            'alamat' => 'Jl. Lama No. 5',
            'lat' => -6.9,
            'lng' => 107.6,
        ]);

        return $user->refresh();
    }
}
