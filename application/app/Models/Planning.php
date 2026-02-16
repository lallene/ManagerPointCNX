<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Planning extends Model
{
    use HasFactory;

    protected $fillable = [
        'jour',
        'entree',
        'sortie',
        'semaine',
        'agent_id',
        'user_id',
    ];

    /**
     * Casts des attributs (Standard Laravel 11).
     * On définit 'jour' comme date et les horaires comme 'immutable_datetime' 
     * ou string selon ton besoin de calcul.
     */
    protected function casts(): array
    {
        return [
            'jour' => 'date',
            // On les laisse souvent en string pour les formats 'H:i' simples,
            // ou on utilise 'datetime' si on veut manipuler Carbon directement.
        ];
    }

    /**
     * Relation avec l'Agent concerné par ce planning.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Relation avec l'utilisateur (Admin/RH) qui a créé le planning.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pointage()
    {

        return $this->hasOne(Pointage::class, 'agent_id', 'agent_id');
    }
}