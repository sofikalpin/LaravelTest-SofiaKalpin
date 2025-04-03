<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToOrdersTable extends Migration
{
    public function up()
    {
        // Check if the "status" column doesn't already exist before adding it
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'status')) {
                $table->enum('status', ['pending', 'processing', 'completed', 'cancelled']);
            }
        });
    }

    public function down()
    {
        // If the column exists, remove it when rolling back the migration
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
}
