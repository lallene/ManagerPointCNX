<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use HasFactory;

    // On conserve le choix de ne pas utiliser de timestamps (created_at/updated_at)
    public $timestamps = false;

    protected $fillable = [
        'designation', 
        'responsable', 
        'contact'
    ];

    /**
     * Relation avec les Projets.
     * Un site peut héberger plusieurs projets ou services.
     */
    public function projets(): HasMany
    {
        // Renommé en camelCase (minuscule) pour la cohérence Laravel
        return $this->hasMany(Projet::class, 'site_id');
    }
}