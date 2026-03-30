<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElementComponent extends Model
{
    protected $fillable = [
        'element_id',
        'component_id',
    ];

    public function element()
    {
        return $this->belongsTo(Element::class);
    }

    public function component()
    {
        return $this->belongsTo(Component::class);
    }
}