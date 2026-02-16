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
            $table->string('workday_id')->unique(); 
            $table->string('prenom');
            $table->string('nom');
            $table->string('work_email')->unique();
            $table->string('fonction');
            
            $table->string('manager')->nullable()->index(); 
            
            $table->foreignId('projet_id')
                ->nullable()
                ->constrained('projets')
                ->onDelete('cascade')
                ->onUpdate('cascade');

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
            Schema::dropIfExists('agents');
        }
    }





