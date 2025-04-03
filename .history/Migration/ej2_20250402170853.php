<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ModifyUserNameToFullNameInUsersTable extends Migration
{
   
    public function up()
    {
        // Create the new column
        Schema::table('users', function (Blueprint $table) {
            $table->string('full_name', 100)->nullable();
        });

        // Copy data from old column to new column
        DB::statement('UPDATE users SET full_name = user_name');

        //Make the new column non-nullable after data transfer
        Schema::table('users', function (Blueprint $table) {
            $table->string('full_name', 100)->nullable(false)->change();
        });

        // Remove the original column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('user_name');
        });
    }

    
    public function down()
    {
        // Recreate the original column
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_name', 50)->nullable();
        });

        // Copy data back from new column to original column
        DB::statement('UPDATE users SET user_name = full_name');

        // Make original column non-nullable after data transfer
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_name', 50)->nullable(false)->change();
        });

        // Remove the new column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('full_name');
        });
    }
}