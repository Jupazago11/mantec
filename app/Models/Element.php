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
        'warehouse_code',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

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

    public function reportDetails()
    {
        return $this->hasMany(ReportDetail::class);
    }
}
