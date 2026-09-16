<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Subrol del modulo Personal (independiente del Role/RoleModulePermission
 * del sistema actual, que existen pero no estan conectados a ninguna
 * verificacion real — se decidio no reutilizarlos). Los 6 booleanos son
 * una lista fija confirmada con el usuario, no un catalogo de modulos
 * dinamico — por eso son columnas simples y no una tabla de permisos
 * aparte.
 */
class PersonalRole extends Model
{
    protected $fillable = [
        'name',
        'ver_empleados',
        'ver_programacion',
        'editar_programacion_sin_limite',
        'ver_diario_campo',
        'cerrar_diario_campo',
        'ver_bitacora',
    ];

    protected function casts(): array
    {
        return [
            'ver_empleados' => 'boolean',
            'ver_programacion' => 'boolean',
            'editar_programacion_sin_limite' => 'boolean',
            'ver_diario_campo' => 'boolean',
            'cerrar_diario_campo' => 'boolean',
            'ver_bitacora' => 'boolean',
        ];
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
