<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Actividad de la Programacion (seccion 6 de
 * NUEVA_FUNCIONALIDAD_PERSONAL_Y_PROGRAMACION.md). "activity_type" ('P'
 * primaria / 'S' secundaria) es una propiedad de la actividad completa,
 * no una marca por persona — ver seccion 6.1. Diario de Campo (seccion 5,
 * Fase 2b) es este mismo registro enriquecido con los campos de cierre
 * administrativo (process..we_code, closed_at) — no una tabla aparte.
 * El registro del supervisor (seccion 7, Fase 3, seccion 14.18 —
 * comments/all_worked_scheduled_hours/reported_hours a nivel de toda la
 * actividad, detalle por persona en employeeHours()) tambien vive aqui,
 * no en una tabla aparte.
 */
class Activity extends Model
{
    protected $fillable = [
        'date',
        'company_id',
        'group_number',
        'area',
        'team',
        'description',
        'scheduling_comment',
        'activity_type',
        'estimated_hours',
        'shift',
        'responsible_employee_id',
        'created_by_employee_id',
        'process',
        'executed_description',
        'corrected_hours',
        'reported_hours',
        'comments',
        'zcom',
        'line_code',
        'ot_sap',
        'acta_entrega',
        'we_code',
        'closed_by_employee_id',
        'closed_at',
        'all_worked_scheduled_hours',
        'hours_registered_by_employee_id',
        'hours_registered_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'estimated_hours' => 'decimal:2',
            'corrected_hours' => 'decimal:2',
            'reported_hours' => 'decimal:2',
            'closed_at' => 'datetime',
            'all_worked_scheduled_hours' => 'boolean',
            'hours_registered_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'responsible_employee_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by_employee_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'closed_by_employee_id');
    }

    public function personas(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class);
    }

    public function hoursRegisteredBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'hours_registered_by_employee_id');
    }

    // Detalle por persona de horas realmente trabajadas (seccion 7,
    // seccion 14.18) — solo tiene filas cuando el supervisor registro que
    // NO todos trabajaron las horas programadas (all_worked_scheduled_hours
    // = false). Ver SupervisorViewController.
    public function employeeHours(): HasMany
    {
        return $this->hasMany(ActivityEmployeeHour::class);
    }

    // Evidencias (foto/video) del registro del supervisor (seccion 7,
    // "el supervisor registra evidencias y comentarios") — pendiente
    // desde la seccion 14.18, resuelto en 14.20. Sube a R2 con
    // ActivityEvidencePathBuilder, prefijo propio "personal-actividades/"
    // separado del que usan los reportes (ReportFilePathBuilder).
    public function evidences(): HasMany
    {
        return $this->hasMany(ActivityEvidence::class);
    }

    // Comentarios del responsable sobre un trabajador puntual de esta
    // actividad (pedido 2026-09-22) — como solo el responsable asignado
    // puede escribir aqui (Api\Personal\ActivityController::store), a lo
    // sumo hay una fila por employee_id (upsert, no se acumulan). Los
    // comentarios de administrativo (nivel dia, no de una actividad
    // puntual) NO viven aqui — ver ActivityEmployeeComment.
    public function employeeComments(): HasMany
    {
        return $this->hasMany(ActivityEmployeeComment::class);
    }

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    // "Valor final" de horas (seccion 5/8): corregida manda sobre
    // reportada, reportada sobre programada. reported_hours ya tiene
    // fuente real desde el registro del supervisor (seccion 7, Fase 3,
    // seccion 14.18) — SupervisorViewController::store() la actualiza con
    // la suma de employeeHours (o con estimated_hours si el supervisor
    // marco que todos trabajaron las horas programadas).
    public function finalHours(): ?string
    {
        return $this->corrected_hours ?? $this->reported_hours ?? $this->estimated_hours;
    }

    // Filas del Diario de Campo (seccion 14.21, pedido 2026-09-19): una
    // fila por cada valor de horas DISTINTO entre las personas de la
    // actividad — no una fila por persona ni un total agregado. Si todas
    // trabajaron lo mismo (o no hay detalle por persona todavia), es 1
    // sola fila con todas las personas. Si 2 trabajaron 8h y otras 2
    // trabajaron 10h, son 2 filas, cada una con sus personas y esa hora.
    // corrected_hours (correccion administrativa) es un valor unico para
    // toda la actividad, nunca por persona, asi que siempre colapsa a 1
    // fila cuando esta presente — igual prioridad que finalHours().
    //
    // @return array<int, array{personas: \Illuminate\Support\Collection, horas: float|null}>
    public function diaryHourGroups(): array
    {
        if ($this->corrected_hours !== null) {
            return [['personas' => $this->personas, 'horas' => (float) $this->corrected_hours]];
        }

        if ($this->all_worked_scheduled_hours === false && $this->employeeHours->isNotEmpty()) {
            $horasPorEmpleado = $this->employeeHours->keyBy('employee_id');

            return $this->personas
                ->groupBy(function ($persona) use ($horasPorEmpleado) {
                    $horas = $horasPorEmpleado->get($persona->id)?->worked_hours;

                    // Cast a string (no float) como llave de agrupacion:
                    // Collection::groupBy() usa PHP arrays por debajo, y
                    // PHP trunca claves float a int (8.5 y 8.0 colisionarian
                    // en la clave 8). worked_hours ya viene como string
                    // por el cast 'decimal:2' del modelo, evita eso.
                    return $horas !== null ? (string) $horas : '__sin_registro__';
                })
                ->map(fn ($personas, $horasKey) => [
                    'personas' => $personas,
                    'horas' => $horasKey === '__sin_registro__' ? null : (float) $horasKey,
                ])
                ->values()
                ->all();
        }

        $horas = $this->reported_hours ?? $this->estimated_hours;

        return [['personas' => $this->personas, 'horas' => $horas !== null ? (float) $horas : null]];
    }
}
