<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planning extends Model
{
    protected $fillable = [
        'jour',
        'heure_debut',
        'heure_fin',
        'semaine',
        'agent_id',
        'user_id',
    ];

    protected $dates = ['jour', 'heure_debut', 'heure_fin'];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}