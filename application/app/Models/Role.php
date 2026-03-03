<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'guard_name'];

    /**
     * Récupérer tous les agents qui ont ce rôle.
     */
    public function agents(): MorphToMany
    {
        // Spatie utilise une relation morphologique (MorphToMany)
        return $this->morphedByMany(Agent::class, 'model', 'model_has_roles', 'role_id', 'model_id');
    }
}