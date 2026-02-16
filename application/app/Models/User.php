<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    // Suppression de HasApiTokens (Sanctum)
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'work_email',
        'password',
        'password_first_connection',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        // Note : On ne cache 'password_first_connection' que si on utilise des APIs JSON.
        // Si tu en as besoin dans tes vues Blade ou ton Middleware, laisse-le visible.
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        // On cast le flag en boolean pour simplifier les tests (if($user->password_first_connection))
        'password_first_connection' => 'boolean', 
        'password' => 'hashed', // Laravel 11 gère le hashage automatique si configuré
    ];
    
    /**
     * Relation avec l'Agent
     * Un utilisateur est lié à un agent par son email professionnel.
     */
    public function agent()
    {
        return $this->hasOne(Agent::class, 'work_email', 'work_email');
    }

    /**
     * Helper pour savoir si l'utilisateur est un admin (Optionnel)
     */
    public function isIT(): bool
    {
        return $this->hasRole('IT');
    }

    public function projets()
{
    return $this->belongsToMany(Projet::class, 'projet_user');
}
}