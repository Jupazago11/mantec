<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'name',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'client_user')
            ->withTimestamps();
    }

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class);
    }

    public function elementTypes(): HasMany
    {
        return $this->hasMany(ElementType::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(Component::class);
    }

    public function diagnostics(): HasMany
    {
        return $this->hasMany(Diagnostic::class);
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(Condition::class);
    }

    public function inspectorElementTypes(): BelongsToMany
    {
        return $this->belongsToMany(ElementType::class, 'user_client_element_type')
            ->withPivot('user_id')
            ->withTimestamps();
    }
}
