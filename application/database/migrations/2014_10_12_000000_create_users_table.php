<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
        public function up()
        {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            
            // On ajoute ->index() car tu fais des jointures SQL dessus dans UtilisateurController::ajax
            $table->string('work_email')->unique()->index(); 
            
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            
            /**
             * Correction Lead Dev : 
             * Au lieu d'un string, on utilise un boolean pour le flag de première connexion.
             * C'est plus léger en base et plus simple à tester : if($user->password_first_connection)
             */
            $table->boolean('password_first_connection')->default(true);

            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
