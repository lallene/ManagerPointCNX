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

    public function store(Request $request)
{
    // 1. Validation des données entrantes
    $request->validate([
        'plannings' => 'required|array',
        'week' => 'required'
    ]);

    $data = $request->input('plannings');

    try {
        foreach ($data as $agentId => $jours) {
            foreach ($jours as $date => $heures) {
                
                // On n'enregistre que si au moins une heure est saisie
                if (!empty($heures['entree']) || !empty($heures['sortie'])) {
                    
                    Planning::updateOrCreate(
                        [
                            'agent_id' => $agentId,
                            'jour'     => $date,
                        ],
                        [
                            'entree'   => $heures['entree'],
                            'sortie'   => $heures['sortie'],
                            'semaine'  => $request->input('week'), // Ex: 2026-08
                            // 'updated_by' => auth()->id(), // Optionnel : pour le suivi
                        ]
                    );
                }
            }
        }

        return back()->with('success', 'Le planning de la semaine ' . $request->input('week') . ' a été enregistré avec succès.');

    } catch (\Exception $e) {
        // Log de l'erreur pour le debug en tant que Lead Dev
        \Log::error("Erreur lors de l'enregistrement du planning : " . $e->getMessage());
        
        return back()->with('error', 'Une erreur est survenue lors de l\'enregistrement.');
    }
}
}