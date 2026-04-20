<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeasurementThicknessDraft extends Model
{
    protected $fillable = [
        'element_id',
        'created_by',
        'updated_by',
    ];

    public function element(): BelongsTo
    {
        return $this->belongsTo(Element::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(MeasurementThicknessDraftLine::class, 'draft_id')
            ->orderBy('cover_number');
    }
}