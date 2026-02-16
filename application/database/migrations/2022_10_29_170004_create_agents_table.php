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
                $table->id();
                $table->string('workday_id')->unique()->index(); 
                $table->string('nom');
                $table->string('prenom');
                $table->string('work_email')->unique()->index(); 
                $table->string('fonction')->nullable();
                $table->string('manager')->nullable(); 
                $table->foreignId('projet_id')->constrained('projets')->onDelete('cascade');

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





