<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Condition extends Model
{
    protected $fillable = [
        'client_id',
        'code',
        'name',
        'description',
        'severity',
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

    public function reportDetails()
    {
        return $this->hasMany(ReportDetail::class);
    }

    public function hasDependencies(): bool
    {
        return $this->reportDetails()->exists();
    }
}