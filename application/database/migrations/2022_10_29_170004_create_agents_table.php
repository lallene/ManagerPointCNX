    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    class CreateAgentsTable extends Migration
    {
        /**
         * Run the migrations.
         *
         * @return void
         */
        public function up()
        {
            Schema::create('agents', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->string('workday_id');
                $table->string('nom');
                $table->string('prenom');
                $table->string('work_email')->unique();
                $table->string('fonction');
                $table->string('manager')->nullable();
                $table->unsignedBigInteger('projet_id')->nullable();
                $table->string('Matricule_salarie')->nullable();
                $table->string('responsable')->nullable();
                $table->foreign('projet_id')
                    ->references('id')
                    ->on('projets')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');         
            });
        }

        /**
         * Reverse the migrations.
         *
         * @return void
         */
        public function down()
        {
            Schema::dropIfExists('agents');
        }
    }





