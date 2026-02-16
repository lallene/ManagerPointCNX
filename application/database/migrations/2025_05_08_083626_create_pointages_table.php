<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePointagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
        public function up()
{
    Schema::create('pointages', function (Blueprint $table) {
        $table->id();

        // Relations
        $table->foreignId('agent_id')->constrained('agents')->onDelete('cascade');
        $table->foreignId('planning_id')->constrained('plannings')->onDelete('cascade');
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); 
        
        // Infos temporelles
        $table->date('date_pointage')->index();
        $table->string('semaine')->index();
        
        // Heures
        $table->time('entree');
        $table->time('pause_debut')->nullable();
        $table->time('pause_fin')->nullable();
        $table->time('sortie');
        
        // Calculs et métadonnées
        $table->integer('minutes_travaillees')->default(0); 
        $table->time('heure_sup')->nullable();
        $table->text('commentaires')->nullable();
        
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pointages');
    }
}
