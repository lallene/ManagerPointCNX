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
     * Helper pour savoir si l'utilisateur est un admin (Optionnel)
     */
    public function isIT(): bool
    {
        return $this->hasRole('IT');
    }

    /**
     * Relation avec l'Agent
     */
    public function agent()
    {
        return $this->hasOne(Agent::class, 'work_email', 'work_email');
    }

    /**
     * Correction Lead Dev : Récupérer les projets via l'Agent rattaché
     * On ne peut pas faire un belongsToMany direct ici car la table pivot 
     * est liée à 'agent_id' et non 'user_id'.
     */
    public function getProjetsAttribute()
    {
        return $this->agent ? $this->agent->projets : collect();
    }

    // Si tu veux quand même pouvoir faire $user->projets()->...
    public function projets()
    {
        // On récupère l'ID de l'agent lié à cet utilisateur
        $agentId = $this->agent ? $this->agent->id : null;
        
        // On définit la relation en pointant sur la table pivot réelle
        return $this->belongsToMany(Projet::class, 'agent_projet', 'agent_id', 'projet_id')
                    ->where('agent_id', $agentId);
    }

}