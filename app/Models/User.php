<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Crypt;

class User extends Authenticatable
{
    use HasUuids;

    protected $fillable = [
        'username', 'email', 'password', 'encrypted_password',
        'first_name', 'last_name', 'phone', 'status', 'deleted_with_data_kept',
        'is_admin', 'newsletter_optin', 'navidrome_id', 'stripe_customer_id',
        'email_verified_at', 'display_name', 'avatar_path',
    ];

    protected $hidden = ['password', 'remember_token', 'encrypted_password'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'newsletter_optin' => 'boolean',
            'deleted_with_data_kept' => 'boolean',
        ];
    }

    /**
     * Store an encrypted copy of a plaintext password.
     * Used only for restoring Navidrome access after suspension — this is
     * deliberately NOT the user's account login password (see
     * generateNavidromePassword()), so that a compromise of this field or of
     * APP_KEY never exposes a password the user might reuse elsewhere.
     */
    public function storeEncryptedPassword(string $plaintext): void
    {
        $this->encrypted_password = Crypt::encryptString($plaintext);
        $this->save();
    }

    /**
     * Retrieve the decrypted Navidrome-only password.
     */
    public function getDecryptedPassword(): ?string
    {
        if (!$this->encrypted_password) {
            return null;
        }
        return Crypt::decryptString($this->encrypted_password);
    }

    /**
     * Generate and store a fresh random password to use as this user's
     * Navidrome credential. Independent from the account's login password
     * so the two never need to (and never should) match.
     */
    public function generateNavidromePassword(): string
    {
        $password = bin2hex(random_bytes(16));
        $this->storeEncryptedPassword($password);
        return $password;
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->where('status', 'active');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}") ?: $this->username;
    }
}
