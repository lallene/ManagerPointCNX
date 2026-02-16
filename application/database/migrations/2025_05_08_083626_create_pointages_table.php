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
        $table->string('semaine', 10);
        $table->date('date_pointage')->index();
        
        $table->time('entree')->nullable();
        $table->time('pause_debut')->nullable();
        $table->time('pause_fin')->nullable();
        $table->time('sortie')->nullable();
        
        $table->integer('minutes_travaillees')->default(0);
        $table->decimal('heure_sup', 5, 2)->default(0);
        $table->text('commentaires')->nullable();

        $table->foreignId('planning_id')->nullable()->constrained('plannings')->onDelete('set null');
        $table->foreignId('agent_id')->constrained('agents')->onDelete('cascade');
        $table->foreignId('user_id')->constrained('users'); // L'admin qui valide
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
