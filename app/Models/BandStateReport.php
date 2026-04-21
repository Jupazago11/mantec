<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BandStateReport extends Model
{
    protected $fillable = [
        'element_id',
        'report_date',
        'description',
        'width',
        'top_cover',
        'bottom_cover',
        'created_by',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'published_at' => 'datetime',
            'width' => 'decimal:2',
            'top_cover' => 'decimal:2',
            'bottom_cover' => 'decimal:2',
        ];
    }

    public function element(): BelongsTo
    {
        return $this->belongsTo(Element::class, 'element_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}