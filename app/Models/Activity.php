<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Actividad de la Programacion (seccion 6 de
 * NUEVA_FUNCIONALIDAD_PERSONAL_Y_PROGRAMACION.md). "activity_type" ('P'
 * primaria / 'S' secundaria) es una propiedad de la actividad completa,
 * no una marca por persona — ver seccion 6.1. Diario de Campo (seccion 5,
 * Fase 2b) es este mismo registro enriquecido con los campos de cierre
 * administrativo (process..we_code, closed_at) — no una tabla aparte.
 * Bitacora real sigue pendiente.
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
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'estimated_hours' => 'decimal:2',
            'corrected_hours' => 'decimal:2',
            'reported_hours' => 'decimal:2',
            'closed_at' => 'datetime',
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

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    // "Valor final" de horas (seccion 5/8): corregida manda sobre
    // reportada, reportada sobre programada. reported_hours todavia no
    // tiene fuente real (la app aun no existe, seccion 7), pero se deja
    // contemplada en la regla para cuando exista.
    public function finalHours(): ?string
    {
        return $this->corrected_hours ?? $this->reported_hours ?? $this->estimated_hours;
    }
}
