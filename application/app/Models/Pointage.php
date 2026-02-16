<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Pointage extends Model
{
    use HasFactory;

    protected $fillable = [
        'semaine', 'planning_id', 'user_id', 'agent_id', 
        'date_pointage', 'entree', 'pause_debut', 'pause_fin', 
        'sortie', 'minutes_travaillees', 'heure_sup', 'commentaires'
    ];

    protected $casts = [
        'date_pointage' => 'date',
        // Note du Lead : On garde datetime pour les calculs Carbon, 
        // le formatage se fera dans la vue ou via les accessors.
        'entree'        => 'datetime',
        'pause_debut'   => 'datetime',
        'pause_fin'     => 'datetime',
        'sortie'        => 'datetime',
    ];

    // --- RELATIONS ---

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Relation vers le planning.
     * Note du Lead : Puisque tu as 'planning_id' dans ton fillable, 
     * Laravel va lier directement via cette clé.
     */
    public function planning()
    {
        return $this->belongsTo(Planning::class);
    }

    /**
     * L'utilisateur (User) qui a saisi ou validé le pointage
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // --- CALCULS & LOGIQUE MÉTIER ---

    /**
     * Calcule la durée réelle de travail moins la pause.
     * On l'utilise généralement dans un Observer ou avant le Save.
     */
    public function calculerMinutesEffectives(): int
    {
        if (!$this->entree || !$this->sortie) return 0;

        $totalMinutes = $this->entree->diffInMinutes($this->sortie);

        if ($this->pause_debut && $this->pause_fin) {
            $pauseMinutes = $this->pause_debut->diffInMinutes($this->pause_fin);
            $totalMinutes -= $pauseMinutes;
        }

        return max(0, (int)$totalMinutes);
    }

    /**
     * Accessor pour l'affichage : $pointage->temps_formatte
     */
    public function getTempsFormatteAttribute(): string
    {
        $heures = floor($this->minutes_travaillees / 60);
        $minutes = $this->minutes_travaillees % 60;
        return sprintf('%dh %02d', $heures, $minutes);
    }

    /**
     * Accessor pour l'écart : $pointage->valeur_ecart
     * Permet de comparer directement avec le planning lié
     */
    public function getValeurEcartAttribute(): int
    {
        if (!$this->planning) return 0;
        return (int)$this->minutes_travaillees - (int)$this->planning->minutes_prevues;
    }

    /**
 * Détermine si l'agent est en retard par rapport au planning.
 * Utilisé dans la vue par $p->is_late
 */
public function getIsLateAttribute(): bool
{
    if (!$this->entree || !$this->planning || !$this->planning->entree) {
        return false;
    }

    // On compare l'heure réelle et l'heure prévue (en ignorant la date pour ne comparer que l'heure)
    $heurePrevue = Carbon::parse($this->planning->entree)->format('H:i:s');
    $heureReelle = $this->entree->format('H:i:s');

    return $heureReelle > $heurePrevue;
}

/**
 * Calcule l'écart de retard en format HH:mm.
 * Utilisé dans la vue par $p->ecart_retard
 */
public function getEcartRetardAttribute(): ?string
{
    if (!$this->is_late) return null;

    $prevu = Carbon::parse($this->planning->entree);
    $reel = $this->entree;

    // Différence absolue entre les deux heures
    return $reel->diff($prevu)->format('%H:%I');
}
}