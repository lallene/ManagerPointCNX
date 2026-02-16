<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlanningsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::create('plannings', function (Blueprint $table) {
            $table->id();
            $table->date('jour')->index();
            $table->time('entree');
            $table->time('sortie');
            $table->integer('semaine')->index(); // Indexé pour les filtres de Dashboard
            
            $table->foreignId('agent_id')->constrained('agents')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users'); // Qui a créé le planning
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
        Schema::dropIfExists('plannings');
    }
}
