<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Element extends Model
{
    protected $fillable = [
        'name',
        'code',
        'area_id',
        'element_type_id',
        'warehouse_code',
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

    public function elementType()
    {
        return $this->belongsTo(ElementType::class);
    }

    public function components()
    {
        return $this->belongsToMany(Component::class, 'element_components');
    }
}