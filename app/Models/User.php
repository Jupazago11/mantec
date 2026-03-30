<?php

namespace App\Models;


use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Report;
use App\Models\ReportDetail;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'document',
        'username',
        'email',
        'password',
        'role_id',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'client_user');
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function reportDetails()
    {
        return $this->hasMany(ReportDetail::class);
    }

    public function hasTraceability(): bool
    {
        return $this->reports()->exists() || $this->reportDetails()->exists();
    }
}