<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = [
        'client_id',
        'name',
        'description',
        'auto_sync',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'auto_sync' => 'boolean',
            'status' => 'boolean',
        ];
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function elements()
    {
        return $this->hasMany(Element::class);
    }

    public function hasDependencies(): bool
    {
        return $this->elements()->exists();
    }

    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class, 'group_user')
            ->withTimestamps();
    }
}
