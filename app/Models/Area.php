<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $fillable = [
        'name',
        'code',
        'client_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
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
}