<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name',
        'company_name',
        'email',
        'phone',
        'address'
    ];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function profile()
    {
        return $this->hasOne(ClientProfile::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function invitations()
    {
        return $this->hasMany(ClientInvitation::class);
    }
}
