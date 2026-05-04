<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BandEventDraftEvidence extends Model
{
    protected $table = 'band_event_draft_evidences';

    protected $fillable = [
        'band_event_draft_id',
        'disk',
        'file_path',
        'file_type',
        'file_name',
        'mime_type',
        'size_bytes',
        'sort_order',
        'created_by',
    ];

    public function draft()
    {
        return $this->belongsTo(BandEventDraft::class, 'band_event_draft_id');
    }
}
