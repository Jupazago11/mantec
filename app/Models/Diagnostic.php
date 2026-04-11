<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Diagnostic extends Model
{
    protected $fillable = [
        'client_id',
        'element_type_id',
        'name',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function elementType(): BelongsTo
    {
        return $this->belongsTo(ElementType::class);
    }

    public function components(): BelongsToMany
    {
        return $this->belongsToMany(
            Component::class,
            'component_diagnostics',
            'diagnostic_id',
            'component_id'
        )->withTimestamps();
    }

    public function reportDetails(): HasMany
    {
        return $this->hasMany(ReportDetail::class);
    }
}
