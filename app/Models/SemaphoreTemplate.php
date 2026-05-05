<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SemaphoreTemplate extends Model
{
    protected $fillable = [
        'client_id',
        'group_id',
        'element_type_id',
        'name',
        'description',
        'is_default',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'status' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function elementType(): BelongsTo
    {
        return $this->belongsTo(ElementType::class);
    }

    public function columns(): HasMany
    {
        return $this->hasMany(SemaphoreTemplateColumn::class)
            ->orderBy('position')
            ->orderBy('id');
    }
}
