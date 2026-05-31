<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupReportConfigColumn extends Model
{
    protected $fillable = [
        'config_id',
        'column_key',
        'label',
        'position',
        'visible',
        'can_edit_admin_cliente',
        'can_edit_observador',
        'can_edit_observador_cliente',
    ];

    protected function casts(): array
    {
        return [
            'visible'                    => 'boolean',
            'can_edit_admin_cliente'     => 'boolean',
            'can_edit_observador'        => 'boolean',
            'can_edit_observador_cliente'=> 'boolean',
        ];
    }

    public function config()
    {
        return $this->belongsTo(GroupReportConfig::class, 'config_id');
    }
}
