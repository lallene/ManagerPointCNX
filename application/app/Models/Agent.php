<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    use HasFactory;
        /**
     * The model's default values for attributes.
     *
     * @var array
     */


    public $fillable = [
        'nom',
        'prenom',
        'projet_id',
        'workday_id',
        'email',
        'manager',
        'fonction'
    ];

    public $timestamps = false;

    public function Projet(){
        return $this->belongsTo(Projet::class, 'projet_id');
    }


    public function manager()
{
    return $this->belongsTo(Agent::class, 'manager', 'workday_id');
}
public function user()
{
    return $this->belongsTo(User::class, 'email', 'email');
}

public function plannings()
{
    return $this->hasMany(Planning::class, 'agent_id');
}

}

