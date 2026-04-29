<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserClientGroupArea extends Model
{
    protected $table = 'user_client_group_areas';

    protected $fillable = [
        'user_id',
        'client_id',
        'group_id',
        'area_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}