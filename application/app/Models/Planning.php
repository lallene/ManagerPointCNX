<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planning extends Model
{
    protected $fillable = [
        'jour',
        'entree',
        'sortie',
        'semaine',
        'agent_id',
        'user_id',
    ];

    protected $dates = ['jour', 'entree', 'sortie'];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}