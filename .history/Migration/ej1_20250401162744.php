<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToOrdersTable extends Migration
{
    public function up()
    {
       //Se verifica que la columa "status" no exista antes de agregarla
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'status')) {
                $table->enum('status', ['pending', 'processing', 'completed', 'cancelled']);
            }
        });
    }

    public function down()
    {
        //En caso de que la columna exista, se la elimina cuando se revierte la migración
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
}
