<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Component extends Model
{
    protected $fillable = [
        'client_id',
        'name',
        'code',
        'element_type_id',
        'is_required',
        'is_default',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_default' => 'boolean',
            'status' => 'boolean',
        ];
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function elementType()
    {
        return $this->belongsTo(ElementType::class);
    }

    public function diagnostics()
    {
        return $this->belongsToMany(Diagnostic::class, 'component_diagnostics');
    }

    public function elements()
    {
        return $this->belongsToMany(Element::class, 'element_components');
    }

    public function reportDetails()
    {
        return $this->hasMany(ReportDetail::class);
    }

    public function hasDependencies(): bool
    {
        return $this->elements()->exists()
            || $this->diagnostics()->exists()
            || $this->reportDetails()->exists();
    }
}