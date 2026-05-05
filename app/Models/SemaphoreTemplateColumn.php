<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SemaphoreTemplateColumn extends Model
{
    protected $fillable = [
        'semaphore_template_id',
        'key',
        'label',
        'description',
        'column_type',
        'severity_direction',
        'empty_state_behavior',
        'source_column_key',
        'position',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SemaphoreTemplate::class, 'semaphore_template_id');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(SemaphoreTemplateColumnRule::class)
            ->orderBy('position')
            ->orderBy('id');
    }
}
