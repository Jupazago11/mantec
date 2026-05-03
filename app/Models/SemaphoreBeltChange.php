<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SemaphoreBeltChange extends Model
{
    protected $fillable = [
        'element_id',
        'year',
        'week',
        'is_belt_change',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'week' => 'integer',
            'is_belt_change' => 'boolean',
        ];
    }

    public function element()
    {
        return $this->belongsTo(Element::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
