<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Projet extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['designation', 'site_id', 'dltsuperviseur', 'msa_id'];

    public function Site(){
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function agents(){
        return $this->hasMany(Agent::class);
    }
}
