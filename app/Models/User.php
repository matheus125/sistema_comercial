<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'company_id',
    'branch_id',
    'profile_id',
    'name',
    'email',
    'phone',
    'status',
    'failed_login_attempts',
    'locked_until',
    'last_login_at',
    'last_login_ip',
    'password',
])]
#[Hidden(['password', 'remember_token'])]
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
            'email_verified_at' => 'datetime',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function hasPermission(string $permission): bool
    {
        if (!$this->profile_id) {
            return false;
        }

        return cache()->remember(
            "user-permission-{$this->id}-{$permission}",
            60,
            fn () => \DB::table('profile_permissions')
                ->join('permissions', 'permissions.id', '=', 'profile_permissions.permission_id')
                ->where('profile_permissions.profile_id', $this->profile_id)
                ->where('permissions.slug', $permission)
                ->exists()
        );
    }

    public function isAdmin(): bool
    {
        return \DB::table('profiles')
            ->where('id', $this->profile_id)
            ->where('slug', 'administrador')
            ->exists();
    }
}
