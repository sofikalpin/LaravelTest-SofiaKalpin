<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ModifyUserNameToFullNameInUsersTable extends Migration
{
   
    public function up()
    {
        // Primero creamos la nueva columna sin restricciones
        Schema::table('users', function (Blueprint $table) {
            $table->string('full_name', 100)->nullable();
        });

        // Copiamos los datos de la columna antigua a la nueva
        DB::statement('UPDATE users SET full_name = user_name');

        // Aseguramos que la nueva columna no sea nula
        Schema::table('users', function (Blueprint $table) {
            $table->string('full_name', 100)->nullable(false)->change();
        });

        // Eliminamos la columna original
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('user_name');
        });
    }

  
    public function down()
    {
        // Recreamos la columna original
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_name', 50)->nullable();
        });

        // Copiamos los datos de vuelta
        DB::statement('UPDATE users SET user_name = full_name');

        // Aseguramos que la columna original no sea nula
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_name', 50)->nullable(false)->change();
        });

        // Eliminamos la nueva columna
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('full_name');
        });
    }
}

