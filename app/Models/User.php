<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Enums\VerificationStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['nama', 'email', 'password', 'no_hp', 'role', 'status_akun'])]
#[Hidden(['password'])]
#[WithoutTimestamps]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => Role::class,
            'status_akun' => AccountStatus::class,
        ];
    }

    /**
     * Determine whether the account may hold an authenticated session.
     */
    public function isActive(): bool
    {
        return $this->status_akun?->isActive() ?? false;
    }

    /**
     * Message shown when the account may not sign in.
     *
     * For blocked accounts this returns the latest blocking notification so the
     * administrator's reason stays visible even though the account can no
     * longer open the in-app notification list.
     */
    public function refusalMessage(): ?string
    {
        if ($this->status_akun !== AccountStatus::Diblokir) {
            return $this->status_akun?->refusalMessage();
        }

        $reason = InAppNotification::query()
            ->where('user_id', $this->id)
            ->where('judul', 'Akun Anda Diblokir')
            ->orderByDesc('id')
            ->value('pesan');

        return is_string($reason) && $reason !== '' ? $reason : $this->status_akun->refusalMessage();
    }

    /**
     * Determine whether the administrator has requested document completeness.
     */
    public function hasDocumentCompletenessRequest(): bool
    {
        return $this->verificationDocuments()
            ->where('status', VerificationStatus::PerluKelengkapan->value)
            ->exists();
    }

    /**
     * Determine whether the latest document for any type is rejected or incomplete.
     */
    public function hasUnresolvedVerificationDocuments(): bool
    {
        return $this->verificationDocuments()
            ->whereIn('id', $this->latestVerificationDocumentIds())
            ->whereIn('status', [
                VerificationStatus::PerluKelengkapan->value,
                VerificationStatus::Ditolak->value,
            ])
            ->exists();
    }

    /**
     * Get the newest document id for each document type.
     *
     * @return array<int, int>
     */
    public function latestVerificationDocumentIds(): array
    {
        return $this->verificationDocuments()
            ->selectRaw('MAX(id) as latest_id')
            ->groupBy('jenis_dokumen')
            ->pluck('latest_id')
            ->all();
    }

    /**
     * Determine whether this pending account may sign in to complete documents.
     */
    public function mayAccessDocumentCompleteness(): bool
    {
        return $this->status_akun === AccountStatus::Menunggu
            && $this->hasDocumentCompletenessRequest();
    }

    /**
     * Determine whether the account holds one of the given roles.
     */
    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * Limit the query to Donor and Penerima accounts awaiting verification.
     */
    public function scopePendingVerification(Builder $query): void
    {
        $query->whereIn('role', [Role::Donor->value, Role::Penerima->value])
            ->where('status_akun', AccountStatus::Menunggu->value);
    }

    /**
     * The named route for this account's role dashboard.
     */
    public function dashboardRouteName(): string
    {
        return match ($this->role) {
            Role::Administrator => 'dashboard.admin',
            Role::Donor => 'dashboard.donor',
            Role::Penerima => 'dashboard.recipient',
            default => 'dashboard.donor',
        };
    }

    /**
     * The business or institution name for this account's role.
     */
    public function profileName(): ?string
    {
        return match ($this->role) {
            Role::Donor => $this->donorProfile?->nama_usaha,
            Role::Penerima => $this->recipientProfile?->nama_lembaga,
            default => null,
        };
    }

    /**
     * The donor profile of this account, if the account is a donor.
     */
    public function donorProfile(): HasOne
    {
        return $this->hasOne(DonorProfile::class, 'user_id');
    }

    /**
     * The recipient profile of this account, if the account is a recipient.
     */
    public function recipientProfile(): HasOne
    {
        return $this->hasOne(RecipientProfile::class, 'user_id');
    }

    /**
     * The legal documents this account uploaded for verification.
     */
    public function verificationDocuments(): HasMany
    {
        return $this->hasMany(VerificationDocument::class, 'user_id');
    }

    /**
     * The in-app notifications addressed to this account.
     */
    public function inAppNotifications(): HasMany
    {
        return $this->hasMany(InAppNotification::class, 'user_id');
    }
}
