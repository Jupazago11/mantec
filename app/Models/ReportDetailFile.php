<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportDetailFile extends Model
{
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
        'size_bytes',
        'sort_order',
    ];

    public function reportDetail()
    {
        return $this->belongsTo(ReportDetail::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
