<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityEvidence extends Model
{
    // "evidence" es incontable en ingles, Eloquent no lo pluraliza solo
    // (inferiria "activity_evidence") — se fija explicito el nombre de
    // tabla real ("activity_evidences", igual que report_detail_files).
    protected $table = 'activity_evidences';

    protected $fillable = [
        'activity_id',
        'uploaded_by_employee_id',
        'disk',
        'path',
        'original_name',
        'stored_name',
        'mime_type',
        'extension',
        'file_type',
        'size_bytes',
        'sort_order',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'uploaded_by_employee_id');
    }
}
