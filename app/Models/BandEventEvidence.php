<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BandEventEvidence extends Model
{
    protected $table = 'band_event_evidences';

    protected $fillable = [
        'band_event_id',

        // Archivo
        'file_path',
        'file_type', // image / video
        'file_name',

        // Control
        'uploaded_by',
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