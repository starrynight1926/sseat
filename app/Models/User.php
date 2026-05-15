<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    public const ROLE_CUSTOMER    = 'customer';
    public const ROLE_CASHIER     = 'cashier';
    public const ROLE_BUILDER     = 'builder';
    public const ROLE_MANAGER     = 'manager';
    public const ROLE_ADMIN       = 'admin';
    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLES = [
        self::ROLE_CUSTOMER,
        self::ROLE_CASHIER,
        self::ROLE_BUILDER,
        self::ROLE_MANAGER,
        self::ROLE_ADMIN,
        self::ROLE_SUPER_ADMIN,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_suspended',
    ];

    public function shops()
    {
        return $this->hasMany(Shop::class);
    }

    public function isCustomer(): bool  { return $this->role === self::ROLE_CUSTOMER; }
    public function isCashier(): bool   { return $this->role === self::ROLE_CASHIER; }
    public function isBuilder(): bool   { return $this->role === self::ROLE_BUILDER; }
    public function isManager(): bool   { return $this->role === self::ROLE_MANAGER; }
    public function isAdmin(): bool     { return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN], true); }
    public function isSuperAdmin(): bool { return $this->role === self::ROLE_SUPER_ADMIN; }

    /** Cashier + Builder + Admin + Super Admin có thể operate (bấm ghế). */
    public function canOperate(): bool
    {
        return in_array($this->role, [self::ROLE_CASHIER, self::ROLE_BUILDER, self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN]);
    }

    /** Builder + Super Admin có thể thiết kế sơ đồ. */
    public function canBuild(): bool
    {
        return in_array($this->role, [self::ROLE_BUILDER, self::ROLE_SUPER_ADMIN]);
    }

    /** Manager + Admin + Super Admin có thể duyệt sơ đồ. */
    public function canApprove(): bool
    {
        return in_array($this->role, [self::ROLE_MANAGER, self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN]);
    }

    /** Admin + Super Admin quản lý user. */
    public function canManageUsers(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN]);
    }

    /** Có thể xem danh sách shop (operate hoặc build). */
    public function canManageShops(): bool
    {
        return in_array($this->role, [self::ROLE_CASHIER, self::ROLE_BUILDER, self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN], true);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_suspended' => 'boolean',
        ];
    }
}
