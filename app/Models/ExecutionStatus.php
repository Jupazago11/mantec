<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExecutionStatus extends Model
{
    protected $fillable = [
        'code',
        'name',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }
}