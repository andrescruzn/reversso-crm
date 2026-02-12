<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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
        ];
    }

    /**
     * Relación con Roles.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'role_user',
            'user_id',
            'role_id'
        )->withTimestamps();
    }

    /**
     * Verifica si el usuario tiene un rol específico (String).
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()
            ->where(function ($q) use ($role) {
                $q->where('display_name', $role)
                ->orWhere('name', $role);
            })
            ->exists();
    }

    /**
     * ✅ CORRECCIÓN: Método para verificar múltiples roles (Array).
     * Este es el método que usaremos en la vista.
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('display_name', $roles)->exists();
    }

    // JWT Methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->roles->first()?->name,
        ];
    }

    /**
     * Verifica si el usuario está activo.
     * Esto asume que tienes una columna 'is_active' en tu tabla 'users'.
     */
    public function isActive(): bool
    {
        // Si no tienes la columna 'is_active', puedes retornar true por ahora
        // para que te deje entrar, o usar otra lógica.
        return (bool) ($this->is_active ?? true);
    }
}
