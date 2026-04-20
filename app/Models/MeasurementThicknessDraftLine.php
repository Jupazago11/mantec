<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeasurementThicknessDraftLine extends Model
{
    protected $fillable = [
        'draft_id',
        'cover_number',
        'top_left',
        'top_center',
        'top_right',
        'bottom_left',
        'bottom_center',
        'bottom_right',
        'hardness_left',
        'hardness_center',
        'hardness_right',
    ];

    protected function casts(): array
    {
        return [
            'top_left' => 'decimal:2',
            'top_center' => 'decimal:2',
            'top_right' => 'decimal:2',
            'bottom_left' => 'decimal:2',
            'bottom_center' => 'decimal:2',
            'bottom_right' => 'decimal:2',
            'hardness_left' => 'decimal:2',
            'hardness_center' => 'decimal:2',
            'hardness_right' => 'decimal:2',
        ];
    }

    public function draft(): BelongsTo
    {
        return $this->belongsTo(MeasurementThicknessDraft::class, 'draft_id');
    }
}