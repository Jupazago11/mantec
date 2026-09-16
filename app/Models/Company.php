<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Empresa cliente (sitio donde se presta mano de obra de mantenimiento —
 * ver nota de terminologia en la seccion 1 de
 * NUEVA_FUNCIONALIDAD_PERSONAL_Y_PROGRAMACION.md, distinto de App\Models\Client).
 * Simplificada segun la seccion 13.5: solo nombre/defecto/archivada, sin
 * inducciones obligatorias por empresa (eso era parte de la gestion
 * documental completa que no se contrato en el Escalon B).
 */
class Company extends Model
{
    protected $fillable = [
        'name',
        'is_default',
        'archived',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'archived' => 'boolean',
        ];
    }
}
