<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
// AJOUTE CETTE LIGNE ICI :
use Illuminate\Database\Eloquent\Relations\BelongsToMany; 

class Agent extends Model
{
    use HasFactory;

    protected $fillable = [
        'workday_id',
        'nom',
        'prenom',
        'work_email',
        'manager', 
        'fonction'
    ];

    public $timestamps = false;

    /**
     * Relation Many-to-Many vers les Projets
     */
    public function projets(): BelongsToMany
    {
        // Laravel va maintenant reconnaître correctement le type BelongsToMany
        return $this->belongsToMany(Projet::class, 'agent_projet', 'agent_id', 'projet_id');
    }

    /**
     * Scope pour filtrer par projet via la table pivot
     */
    public function scopeForProjet($query, $projetId)
    {
        return $query->whereHas('projets', function($q) use ($projetId) {
            $q->where('projets.id', $projetId);
        });
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'manager', 'workday_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Agent::class, 'manager', 'workday_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'work_email', 'work_email');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->prenom} {$this->nom}";
    }

    
    public function pointages(): HasMany
    {
        // Assurez-vous que la colonne dans la table pointages est bien 'agent_id'
        return $this->hasMany(Pointage::class, 'agent_id');
    }
    
    public function roles(): BelongsToMany
{
    return $this->belongsToMany(
        Role::class,
        'model_has_roles', // table pivot par défaut pour spatie/laravel-permission
        'model_id',        // clé dans model_has_roles pour Agent
        'role_id'          // clé de la table roles
    )->where('model_type', self::class); // obligatoire si tu utilises spatie
}


public function plannings(): HasMany
{
    return $this->hasMany(Planning::class, 'agent_id');
}

public function scopeOnlyManagers(Builder $query): Builder
    {
        return $query->whereHas('roles', function($q) {
            $q->where('name', 'Manager');
        });
    }
}