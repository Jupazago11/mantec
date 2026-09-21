<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * "Rol" superior de la jerarquia Rol -> Subrol del modulo Personal (ej.
 * Campo, Administrativos, y cualquiera que se cree despues). Cada
 * PersonalRole (el "Subrol" en el lenguaje del usuario) pertenece a una
 * de estas. Ver migracion 2026_09_17_130000_create_personal_categories_table.
 */
class PersonalCategory extends Model
{
    protected $fillable = [
        'name',
        'activo',
        'responsable_actividad',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'responsable_actividad' => 'boolean',
        ];
    }

    public function roles(): HasMany
    {
        return $this->hasMany(PersonalRole::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
