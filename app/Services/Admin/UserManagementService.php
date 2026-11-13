<?php

namespace App\Services\Admin;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\DonorProfile;
use App\Models\RecipientProfile;
use App\Models\User;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Business rules for the administrator user management screen.
 */
class UserManagementService
{
    /**
     * Number of accounts shown on each page.
     */
    public const PER_PAGE = 10;

    /**
     * Paginate accounts with search, role, and status filters.
     *
     * @param  array{q?: string|null, role?: string|null, status?: string|null}  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $search = trim((string) ($filters['q'] ?? ''));
        $role = $filters['role'] ?? null;
        $status = $filters['status'] ?? null;

        $role = is_string($role) && in_array($role, array_column(Role::cases(), 'value'), true) ? $role : null;
        $status = is_string($status) && in_array($status, array_column(AccountStatus::cases(), 'value'), true) ? $status : null;

        return User::query()
            ->with(['donorProfile', 'recipientProfile'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $term = '%'.$search.'%';

                $query->where(function (Builder $subQuery) use ($term): void {
                    $subQuery->where('nama', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('no_hp', 'like', $term)
                        ->orWhereHas('donorProfile', fn (Builder $profile) => $profile->where('nama_usaha', 'like', $term))
                        ->orWhereHas('recipientProfile', fn (Builder $profile) => $profile->where('nama_lembaga', 'like', $term));
                });
            })
            ->when($role !== null, fn (Builder $query) => $query->where('role', $role))
            ->when($status !== null, fn (Builder $query) => $query->where('status_akun', $status))
            ->orderBy('nama')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * Create an active account and its role profile in one transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::create([
                'nama' => $data['nama'],
                'email' => $data['email'],
                'no_hp' => $data['no_hp'],
                'password' => $data['password'],
                'role' => Role::from($data['role']),
                'status_akun' => AccountStatus::Aktif,
            ]);

            $this->syncProfile($user, $data);

            return $user;
        });
    }

    /**
     * Update account details without changing the role or account status.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $payload = [
                'nama' => $data['nama'],
                'email' => $data['email'],
                'no_hp' => $data['no_hp'],
            ];

            if (! empty($data['password'])) {
                $payload['password'] = $data['password'];
            }

            $user->update($payload);
            $this->syncProfile($user, $data);

            return $user->refresh();
        });
    }

    /**
     * Delete an account after checking the rules that keep platform data consistent.
     *
     * @throws DomainException when the account may not be deleted.
     */
    public function delete(User $user, User $actor): void
    {
        if ($user->is($actor)) {
            throw new DomainException('Anda tidak dapat menghapus akun yang sedang Anda gunakan.');
        }

        if ($user->hasRole(Role::Administrator) && User::query()->where('role', Role::Administrator->value)->count() <= 1) {
            throw new DomainException('Administrator terakhir tidak dapat dihapus.');
        }

        if ($user->hasRole(Role::Donor) && $user->donorProfile?->foodPosts()->exists()) {
            throw new DomainException('Akun donor tidak dapat dihapus karena masih memiliki postingan makanan.');
        }

        if ($user->hasRole(Role::Penerima) && $user->recipientProfile?->claims()->exists()) {
            throw new DomainException('Akun penerima tidak dapat dihapus karena masih memiliki klaim.');
        }

        $documentPaths = $user->verificationDocuments()->pluck('file_path');

        DB::transaction(function () use ($user, $documentPaths): void {
            $user->delete();

            foreach ($documentPaths as $path) {
                Storage::disk('local')->delete($path);
            }
        });
    }

    /**
     * Create or update the profile that belongs to the account's role.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncProfile(User $user, array $data): void
    {
        if ($user->hasRole(Role::Donor)) {
            DonorProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nama_usaha' => $data['nama_usaha'],
                    'jenis_usaha' => $data['jenis_usaha'],
                    'alamat' => $data['alamat'],
                    'lat' => $data['lat'],
                    'lng' => $data['lng'],
                ],
            );

            return;
        }

        if ($user->hasRole(Role::Penerima)) {
            RecipientProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nama_lembaga' => $data['nama_lembaga'],
                    'jenis_lembaga' => $data['jenis_lembaga'],
                    'alamat' => $data['alamat'],
                    'lat' => $data['lat'],
                    'lng' => $data['lng'],
                ],
            );
        }
    }
}
