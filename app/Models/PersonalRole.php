<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'personal_category_id',
        'activo',
        'disponible_en_programacion',
        'responsable_actividad',
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
            'activo' => 'boolean',
            'disponible_en_programacion' => 'boolean',
            'responsable_actividad' => 'boolean',
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

    public function personalCategory(): BelongsTo
    {
        return $this->belongsTo(PersonalCategory::class);
    }

    // Subroles que efectivamente otorgan elegibilidad de Responsable: el
    // Subrol debe tener responsable_actividad activo Y pertenecer a un Rol
    // (PersonalCategory) que TAMBIEN lo tenga — el Rol es un requisito, no
    // una alternativa (ver seccion 6 y seccion 14.9 del documento).
    // Centralizado aqui porque ActivityController (selector "Responsable"
    // de Programacion) y EmployeeController (boton "Ver como supervisor",
    // seccion 14.18) deben usar siempre el mismo criterio — si divergieran,
    // un lado ofreceria/mostraria opciones que el otro rechazaria.
    public static function eligibleAsResponsableIds(): \Illuminate\Support\Collection
    {
        return static::where('responsable_actividad', true)
            ->whereIn('personal_category_id', PersonalCategory::where('responsable_actividad', true)->pluck('id'))
            ->pluck('id');
    }
}
