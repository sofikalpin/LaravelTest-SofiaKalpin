<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     * This function adds a "status" column to the "orders" table if it doesn't already exist.
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Check if the "status" column does not exist before adding it
            if (!Schema::hasColumn('orders', 'status')) {
                $table->enum('status', ['pending', 'processing', 'completed', 'cancelled'])
            }
        });
    }

    /**
     * Reverse the migrations.
     * This function removes the "status" column from the "orders" table if it exists.
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Check if the "status" column exists before attempting to remove it
            if (Schema::hasColumn('orders', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
}