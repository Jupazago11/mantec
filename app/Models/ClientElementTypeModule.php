<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientElementTypeModule extends Model
{
    protected $fillable = [
        'client_id',
        'element_type_id',
        'system_module_id',
        'module_enabled',
        'creation_enabled',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'module_enabled' => 'boolean',
            'creation_enabled' => 'boolean',
            'status' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function elementType(): BelongsTo
    {
        return $this->belongsTo(ElementType::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(SystemModule::class, 'system_module_id');
    }
}