<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

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
            'password' => 'hashed',
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

    public function isRole(string $roleKey): bool
    {
        return $this->role?->key === $roleKey;
    }
}
