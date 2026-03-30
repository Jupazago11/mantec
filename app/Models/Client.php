<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name',
        'obs',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'client_user');
    }

    public function areas()
    {
        return $this->hasMany(Area::class);
    }

    public function elementTypes()
    {
        return $this->hasMany(ElementType::class);
    }

    public function components()
    {
        return $this->hasMany(Component::class);
    }

    public function diagnostics()
    {
        return $this->hasMany(Diagnostic::class);
    }

    public function conditions()
    {
        return $this->hasMany(Condition::class);
    }
}