<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BandStateDraft extends Model
{
    protected $fillable = [
        'element_id',
        'description',
        'width',
        'top_cover',
        'bottom_cover',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'width' => 'decimal:2',
            'top_cover' => 'decimal:2',
            'bottom_cover' => 'decimal:2',
        ];
    }

    public function element(): BelongsTo
    {
        return $this->belongsTo(Element::class, 'element_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}