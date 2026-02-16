<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Projet extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['designation', 'site_id', 'dltsuperviseur', 'msa_id'];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    /**
     * Relation Many-to-Many vers les Agents
     * On utilise la table pivot agent_projet
     */
    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(Agent::class, 'agent_projet', 'projet_id', 'agent_id');
    }
}