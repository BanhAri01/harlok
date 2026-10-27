<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'phone', 'business_name', 'region_id', 'notify_wa', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ADMIN = 'admin';

    public const BUYER = 'buyer';

    public const PARTNER = 'partner';

    public const ROLES = [self::ADMIN, self::BUYER, self::PARTNER];

    protected $attributes = [
        'role' => self::BUYER,
        'notify_wa' => false,
        'is_active' => true,
        'is_demo' => false,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notify_wa' => 'boolean',
            'is_active' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class, 'buyer_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ADMIN;
    }

    public function isBuyer(): bool
    {
        return $this->role === self::BUYER;
    }

    public function displayName(): string
    {
        return $this->business_name ?: $this->name;
    }
}
