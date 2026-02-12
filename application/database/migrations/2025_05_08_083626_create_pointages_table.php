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
            $table->timestamps();
            $table->string('semaine');
            $table->date('date');
            $table->time('heure'); // Heure d'enregistrement
            $table->enum('motif', ['arrivee', 'pause', 'depart'])->default('arrivee'); // Motif du pointage
            $table->foreignId('planning_id')->constrained('plannings')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
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
