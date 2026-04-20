<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\SystemModule;
use App\Models\RoleModulePermission;
use App\Models\ClientElementTypeModule;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'document',
        'username',
        'password',
        'role_id',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'client_user')
            ->withTimestamps();
    }

    public function allowedElementTypes(): BelongsToMany
    {
        return $this->belongsToMany(ElementType::class, 'user_client_element_type')
            ->withPivot('client_id')
            ->withTimestamps();
    }

    public function allowedElementTypesForClient(int $clientId)
    {
        return $this->allowedElementTypes()
            ->wherePivot('client_id', $clientId);
    }

    public function hasElementTypeAccess(int $clientId, int $elementTypeId): bool
    {
        return $this->allowedElementTypes()
            ->wherePivot('client_id', $clientId)
            ->where('element_types.id', $elementTypeId)
            ->exists();
    }

    public function allowedAreas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class, 'user_client_element_type_areas')
            ->withPivot('client_id', 'element_type_id')
            ->withTimestamps();
    }

    public function allowedAreasForClientAndElementType(int $clientId, int $elementTypeId)
    {
        return $this->allowedAreas()
            ->wherePivot('client_id', $clientId)
            ->wherePivot('element_type_id', $elementTypeId);
    }

    public function hasAreaAccessForElementType(int $clientId, int $elementTypeId, int $areaId): bool
    {
        return $this->allowedAreas()
            ->wherePivot('client_id', $clientId)
            ->wherePivot('element_type_id', $elementTypeId)
            ->where('areas.id', $areaId)
            ->exists();
    }

    public function isRole(string $roleKey): bool
    {
        return $this->role?->key === $roleKey;
    }

    public function canManageSystemModule(string $moduleKey): bool
    {
        if (!$this->role_id) {
            return false;
        }

        return RoleModulePermission::query()
            ->where('role_id', $this->role_id)
            ->whereHas('module', fn ($query) => $query->where('key', $moduleKey)->where('status', true))
            ->where('can_manage', true)
            ->exists();
    }

    public function canViewSystemModule(string $moduleKey): bool
    {
        if (!$this->role_id) {
            return false;
        }

        return RoleModulePermission::query()
            ->where('role_id', $this->role_id)
            ->whereHas('module', fn ($query) => $query->where('key', $moduleKey)->where('status', true))
            ->where('can_view', true)
            ->exists();
    }

    public function canCreateInSystemModule(string $moduleKey): bool
    {
        if (!$this->role_id) {
            return false;
        }

        return RoleModulePermission::query()
            ->where('role_id', $this->role_id)
            ->whereHas('module', fn ($query) => $query->where('key', $moduleKey)->where('status', true))
            ->where('can_create', true)
            ->exists();
    }

    public function hasEnabledModuleForClientAndElementType(string $moduleKey, int $clientId, int $elementTypeId): bool
    {
        return ClientElementTypeModule::query()
            ->where('client_id', $clientId)
            ->where('element_type_id', $elementTypeId)
            ->where('status', true)
            ->where('module_enabled', true)
            ->whereHas('module', fn ($query) => $query->where('key', $moduleKey)->where('status', true))
            ->exists();
    }

    public function hasCreationEnabledForClientAndElementType(string $moduleKey, int $clientId, int $elementTypeId): bool
    {
        return ClientElementTypeModule::query()
            ->where('client_id', $clientId)
            ->where('element_type_id', $elementTypeId)
            ->where('status', true)
            ->where('module_enabled', true)
            ->where('creation_enabled', true)
            ->whereHas('module', fn ($query) => $query->where('key', $moduleKey)->where('status', true))
            ->exists();
    }
}
