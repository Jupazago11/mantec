<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupReportConfig extends Model
{
    protected $fillable = ['group_id'];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function columns()
    {
        return $this->hasMany(GroupReportConfigColumn::class, 'config_id')->orderBy('position');
    }
}
