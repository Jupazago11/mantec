<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportDetailFile extends Model
{
    use SoftDeletes;

    public const KIND_HALLAZGO = 'hallazgo';
    public const KIND_CORRECCION = 'correccion';

    protected $fillable = [
        'report_detail_id',
        'uploaded_by',
        'disk',
        'path',
        'original_name',
        'stored_name',
        'mime_type',
        'extension',
        'file_type',
        'evidence_kind',
        'size_bytes',
        'sort_order',
        'detached_by',
    ];

    public function isHallazgo(): bool
    {
        return $this->evidence_kind === self::KIND_HALLAZGO;
    }

    public function isCorreccion(): bool
    {
        return $this->evidence_kind === self::KIND_CORRECCION;
    }

    public function reportDetail()
    {
        return $this->belongsTo(ReportDetail::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function detachedBy()
    {
        return $this->belongsTo(User::class, 'detached_by');
    }
}
