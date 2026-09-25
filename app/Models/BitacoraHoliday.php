<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Festivo marcado a mano sobre una fecha puntual (pedido 2026-09-24,
 * ver migracion create_bitacora_holidays_table para el detalle).
 */
class BitacoraHoliday extends Model
{
    protected $fillable = [
        'date',
        'created_by_employee_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by_employee_id');
    }
}
