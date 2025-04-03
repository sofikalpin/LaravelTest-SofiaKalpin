<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ModifyUserNameToFullNameInUsersTable extends Migration
{
    /**
     * Run the migrations.
     * This function renames 'user_name' to 'full_name' in the 'users' table.
     */
    public function up()
    {
        // Add the new 'full_name' column (allowing null values temporarily)
        Schema::table('users', function (Blueprint $table) {
            $table->string('full_name', 100)->nullable();
        });

        // Copy existing data from 'user_name' to 'full_name', handling NULL values
        DB::statement('UPDATE users SET full_name = COALESCE(user_name, "")');

        // Make 'full_name' required after migration
        Schema::table('users', function (Blueprint $table) {
            $table->string('full_name', 100)->nullable(false)->change();
        });

        // Step 4: Drop the old 'user_name' column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('user_name');
        });
    }

    /**
     * Reverse the migrations.
     * This function reverts 'full_name' back to 'user_name'.
     */
    public function down()
    {
        // Recreate 'user_name' column, allowing null values initially
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_name', 50)->nullable();
        });

        // Copy data back from 'full_name' to 'user_name'
        DB::statement('UPDATE users SET user_name = COALESCE(full_name, "")');

        // Make 'user_name' required again
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_name', 50)->nullable(false)->change();
        });

        // Drop 'full_name' column since we're reverting back
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('full_name');
        });
    }
}
