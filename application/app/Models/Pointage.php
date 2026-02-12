<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pointage extends Model
{
    use HasFactory;

    public $fillable = [
       'semaine',
        'date',
        'heure',
        'motif',
        'user_id',
        'planning_id'
    ];

    public function planning()
{
    return $this->belongsTo(Planning::class);
}
    public function User(){
        return $this->belongsTo(User::class, 'user_id');
    }
}
