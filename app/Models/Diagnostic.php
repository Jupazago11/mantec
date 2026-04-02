<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Diagnostic extends Model
{
    protected $fillable = [
        'client_id',
        'name',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

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
}
