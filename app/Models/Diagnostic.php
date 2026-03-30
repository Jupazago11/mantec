<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Diagnostic extends Model
{
    protected $fillable = [
        'client_id',
        'name',
        'code',
        'description',
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

    public function components()
    {
        return $this->belongsToMany(Component::class, 'component_diagnostics');
    }

    public function reportDetails()
    {
        return $this->hasMany(ReportDetail::class);
    }

    public function hasDependencies(): bool
    {
        return $this->components()->exists() || $this->reportDetails()->exists();
    }
}