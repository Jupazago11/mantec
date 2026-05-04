<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BandEventEvidence extends Model
{
    protected $table = 'band_event_evidences';

    protected $fillable = [
        'band_event_id',

        // Archivo
        'disk',
        'file_path',
        'file_type', // image / video
        'file_name',
        'mime_type',
        'size_bytes',
        'sort_order',

        // Control
        'created_by',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function bandEvent()
    {
        return $this->belongsTo(BandEvent::class);
    }
}
