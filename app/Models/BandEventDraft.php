<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BandEventDraft extends Model
{
    protected $table = 'band_event_drafts';

    protected $fillable = [

        'element_id',
        'parent_id',
        'type',

        // REFERENCIA BANDA
        'brand',
        'total_thickness',
        'top_cover_thickness',
        'bottom_cover_thickness',
        'plies',
        'width',
        'length',
        'roll_count',

        // VULCANIZADO
        'temperature',
        'pressure',
        'time',
        'cooling_time',

        // ENTREGA EQUIPO
        'motor_current',
        'alignment',
        'material_accumulation',
        'guard',
        'idler_condition',

        // CAMBIO TRAMO
        'section_brand',
        'section_thickness',
        'section_plies',
        'section_length',
        'section_width',

        // LÓGICA
        'same_reference',

        // COMUNES
        'observation',
        'report_date',

        // CONTROL
        'created_by',
        'updated_by',
        'status',
    ];

    protected $casts = [
        'report_date' => 'date',
        'status' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function element()
    {
        return $this->belongsTo(Element::class);
    }

    public function parent()
    {
        return $this->belongsTo(BandEvent::class, 'parent_id');
    }
}