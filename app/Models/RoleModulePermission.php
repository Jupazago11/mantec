<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SystemModule;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleModulePermission extends Model
{
    protected $fillable = [
        'role_id',
        'system_module_id',
        'can_view',
        'can_create',
        'can_manage',
    ];

    protected function casts(): array
    {
        return [
            'can_view' => 'boolean',
            'can_create' => 'boolean',
            'can_manage' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(SystemModule::class, 'system_module_id');
    }
}