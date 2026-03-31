<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Element extends Model
{
    protected $fillable = [
        'area_id',
        'element_type_id',
        'name',
        'code',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function client()
    {
        return $this->hasOneThrough(
            Client::class,
            Area::class,
            'id',
            'id',
            'area_id',
            'client_id'
        );
    }

    public function elementType()
    {
        return $this->belongsTo(ElementType::class);
    }

    public function components()
    {
        return $this->belongsToMany(Component::class, 'element_components');
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