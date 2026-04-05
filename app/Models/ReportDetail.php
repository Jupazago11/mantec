<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportDetail extends Model
{
    protected $fillable = [
        'report_id',
        'user_id',
        'element_id',
        'component_id',
        'diagnostic_id',
        'year',
        'week',
        'condition_id',
        'observation',
        'recommendation',
        'orden',
        'aviso',
        'is_belt_change',
        'execution_status_id',
        'execution_date',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'week' => 'integer',
            'is_belt_change' => 'boolean',
            'execution_date' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function element()
    {
        return $this->belongsTo(Element::class);
    }

    public function component()
    {
        return $this->belongsTo(Component::class);
    }

    public function diagnostic()
    {
        return $this->belongsTo(Diagnostic::class);
    }

    public function condition()
    {
        return $this->belongsTo(Condition::class);
    }

    public function files()
    {
        return $this->hasMany(ReportDetailFile::class);
    }

    public function executionStatus()
    {
        return $this->belongsTo(ExecutionStatus::class, 'execution_status_id');
    }

}