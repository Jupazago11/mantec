<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historial de comentarios sobre las horas reportadas de un trabajador
 * (pedido 2026-09-22, ver migracion create_activity_employee_comments_table
 * para el detalle completo de la distincion activity_id null/no-null).
 */
class ActivityEmployeeComment extends Model
{
    protected $fillable = [
        'employee_id',
        'date',
        'activity_id',
        'author_employee_id',
        'author_name',
        'comment',
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

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function authorEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'author_employee_id');
    }
}
