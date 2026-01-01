<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function userRole(): HasOne
    {
        return $this->hasOne(UserRole::class);
    }

    public function getRole(): ?string
    {
        return $this->userRole?->role;
    }

    public function hasRole(string $role): bool
    {
        return $this->getRole() === $role;
    }

    public function canModify(): bool
    {
        return in_array($this->getRole(), ['admin', 'accountant']);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }
}
