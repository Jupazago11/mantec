<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComponentDiagnostic extends Model
{
    protected $table = 'component_diagnostics';

    protected $fillable = [
        'component_id',
        'diagnostic_id',
    ];
}