<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Horas realmente trabajadas por una persona en una actividad (seccion 7
 * de NUEVA_FUNCIONALIDAD_PERSONAL_Y_PROGRAMACION.md, seccion 14.18) —
 * solo existe una fila por persona cuando el supervisor registro que NO
 * todos trabajaron las horas programadas de la actividad completa
 * (Activity::all_worked_scheduled_hours = false).
 *
 * start_time/end_time quedan en el esquema pero SIN USO por ahora
 * (pedido 2026-09-19: "no implementemos ahora lo de la hora de ingreso y
 * final" — capturar duracion via hora inicio/hora final queda diferido
 * para una iteracion futura). Hoy solo se captura worked_hours
 * directamente (numero positivo, precargado con estimated_hours de la
 * actividad y editable) — ver SupervisorViewController::store().
 */
class ActivityEmployeeHour extends Model
{
    protected $fillable = [
        'activity_id',
        'employee_id',
        'worked',
        'start_time',
        'end_time',
        'worked_hours',
    ];

    protected function casts(): array
    {
        return [
            'worked' => 'boolean',
            'worked_hours' => 'decimal:2',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
