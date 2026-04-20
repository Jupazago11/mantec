<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemModule extends Model
{
    protected $fillable = [
        'name',
        'key',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(RoleModulePermission::class);
    }

    public function clientElementTypeModules(): HasMany
    {
        return $this->hasMany(ClientElementTypeModule::class);
    }
}