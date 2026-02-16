<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
   use HasApiTokens, Notifiable;

   use HasRoles;

    protected $fillable = [
        'name',
        'work_email',
        'password',
        'password_first_connection',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        // On cache aussi le mot de passe temporaire des réponses API
        'password_first_connection', 
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    
    // Relation avec l'Agent (si un utilisateur est lié à un agent)
    public function agent()
    {
        return $this->hasOne(Agent::class, 'work_email', 'work_email');
    }

}
