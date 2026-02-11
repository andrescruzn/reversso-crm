<?php

declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo de Rol del sistema.
 *
 * RESPONSABILIDAD:
 * Representar roles disponibles (Admin, Conductor).
 */
class Role extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre de la tabla.
     */
    protected $table = 'roles';

    /**
     * Atributos asignables en masa.
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    // =====================================================================
    // RELACIONES
    // =====================================================================

    /**
     * Usuarios que tienen este rol.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'role_user',
            'role_id',
            'user_id'
        )->withTimestamps();
    }
}
