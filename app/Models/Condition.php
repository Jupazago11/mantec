<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Condition extends Model
{
    protected $fillable = [
        'client_id',
        'element_type_id',
        'code',
        'name',
        'description',
        'severity',
        'color',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'severity' => 'integer',
            'status' => 'boolean',
        ];
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function elementType()
    {
        return $this->belongsTo(ElementType::class);
    }

    public function reportDetails()
    {
        return $this->hasMany(ReportDetail::class);
    }

    public function components()
    {
        return $this->belongsToMany(
            Component::class,
            'component_conditions',
            'condition_id',
            'component_id'
        )->withTimestamps();
    }

    public function hasDependencies(): bool
    {
        return $this->reportDetails()->exists();
    }
}
