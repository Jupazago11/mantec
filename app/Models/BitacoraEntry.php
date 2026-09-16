<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Correccion administrativa de un dia/persona en la Bitacora mensual
 * (seccion 8 de NUEVA_FUNCIONALIDAD_PERSONAL_Y_PROGRAMACION.md).
 * "corrected_value" es texto libre a proposito (admite codigos como "L"
 * de licencia), no un decimal. La hora "programada" NO vive aqui — se
 * calcula en vivo desde Activity/activity_employee (ver BitacoraController).
 */
class BitacoraEntry extends Model
{
    protected $fillable = [
        'employee_id',
        'date',
        'corrected_value',
        'comment',
        'corrected_by_employee_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'corrected_by_employee_id');
    }
}
