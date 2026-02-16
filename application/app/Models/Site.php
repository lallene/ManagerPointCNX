<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['designation', 'responsable', 'contact'];

    public function Projets(){
        return $this->hasMany(Projet::class);
    }

}
