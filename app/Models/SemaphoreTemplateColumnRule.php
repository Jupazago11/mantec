<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SemaphoreTemplateColumnRule extends Model
{
    protected $fillable = [
        'semaphore_template_column_id',
        'component_id',
        'diagnostic_id',
        'position',
    ];

    public function column(): BelongsTo
    {
        return $this->belongsTo(SemaphoreTemplateColumn::class, 'semaphore_template_column_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    public function diagnostic(): BelongsTo
    {
        return $this->belongsTo(Diagnostic::class);
    }
}
