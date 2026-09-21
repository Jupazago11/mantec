<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Usuario del modulo "Personal y Programacion" (guard `personal`,
 * completamente independiente del guard `web`/App\Models\User — ver
 * seccion 3 de NUEVA_FUNCIONALIDAD_PERSONAL_Y_PROGRAMACION.md). Superadmin
 * NO tiene fila aqui: sigue entrando por el guard `web` existente, ver
 * App\Support\PersonalGuard. El subrol (PersonalRole) determina que ve y
 * que puede hacer — ver PersonalGuard::can().
 *
 * HasApiTokens (seccion 14.24): tokens Sanctum para la app Android del
 * supervisor, independiente del token de User que ya usa el Inspector.
 * Sanctum es polimorfico puro en este proyecto (auth:sanctum en modo
 * bearer, sin stateful) — no requiere guard/provider adicional, pero
 * como el mismo middleware autentica indistintamente tokens de User o
 * de Employee, las rutas api/personal/* exigen ademas
 * EnsureTokenableIsEmployee para que un token de Inspector no pueda
 * llamar rutas de Employee (y viceversa).
 */
class Employee extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'nombre',
        'nickname',
        'abreviatura',
        'personal_category_id',
        'personal_role_id',
        'activo',
        'has_login',
        'username',
        'password',
        'in_bitacora',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'has_login' => 'boolean',
            'in_bitacora' => 'boolean',
        ];
    }

    public function personalRole(): BelongsTo
    {
        return $this->belongsTo(PersonalRole::class);
    }

    public function personalCategory(): BelongsTo
    {
        return $this->belongsTo(PersonalCategory::class);
    }
}
