<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            // On supprime la clé étrangère d'abord, puis la colonne
            $table->dropForeign(['projet_id']); 
            $table->dropColumn('projet_id');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->foreignId('projet_id')->nullable()->constrained('projets');
        });
    }
};
