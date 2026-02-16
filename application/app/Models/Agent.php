<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
   
  use HasFactory;

    protected $fillable = [
        'workday_id',
        'nom',
        'prenom',
        'projet_id',
        'work_email',
        'manager',
        'fonction'
    ];

    public $timestamps = false;

    // Relation récursive : un agent a un manager (qui est aussi un agent)
    public function manager()
    {
        return $this->belongsTo(Agent::class, 'manager', 'workday_id');
    }

    // Relation inverse : un manager a plusieurs agents
    public function subordinates()
    {
        return $this->hasMany(Agent::class, 'manager', 'workday_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'work_email', 'work_email');
    }

    public function projet()
    {
        return $this->belongsTo(Projet::class, 'projet_id');
    }

}

