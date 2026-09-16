<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Usuario del modulo "Personal y Programacion" (guard `personal`,
 * completamente independiente del guard `web`/App\Models\User — ver
 * seccion 3 de NUEVA_FUNCIONALIDAD_PERSONAL_Y_PROGRAMACION.md). Superadmin
 * NO tiene fila aqui: sigue entrando por el guard `web` existente, ver
 * App\Support\PersonalGuard. El subrol (PersonalRole) determina que ve y
 * que puede hacer — ver PersonalGuard::can().
 */
class Employee extends Authenticatable
{
    protected $fillable = [
        'nombre',
        'nickname',
        'abreviatura',
        'categoria',
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
}
