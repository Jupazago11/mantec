<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ElementType extends Model
{
    protected $fillable = [
        'client_id',
        'name',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(Component::class);
    }

    public function elements(): HasMany
    {
        return $this->hasMany(Element::class);
    }

    public function allowedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_client_element_type')
            ->withPivot('client_id')
            ->withTimestamps();
    }
}
