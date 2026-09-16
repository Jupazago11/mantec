<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Cuota de horas a laborar de un mes especifico (seccion 8, "se define
 * manualmente cada mes, no es una constante fija"). Antes vivia en
 * localStorage en el mockup; aqui es un valor real por año-mes.
 */
class BitacoraQuota extends Model
{
    protected $fillable = [
        'year',
        'month',
        'quota_hours',
    ];

    protected function casts(): array
    {
        return [
            'quota_hours' => 'decimal:2',
        ];
    }
}
