<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
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
        ];
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
}
