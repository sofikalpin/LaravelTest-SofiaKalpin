<?php
/**
 * Ejercicio 3: Resolución de Dependencias en Migraciones Laravel
 * 
 * PROBLEMA:
 * - Migración 2023_01_01_000000_create_categories_table.php omitida accidentalmente
 * - Migración 2023_01_01_000001_create_products_table.php ya ejecutada en producción
 * - La tabla products tiene una clave foránea a categories, pero categories no existe
 */

// SOLUCIÓN:

/**
 * PASO 1: Crear nueva migración para la tabla categories omitida
 * Comando: php artisan make:migration create_missing_categories_table
 */
class CreateMissingCategoriesTable extends \Illuminate\Database\Migrations\Migration
{
    public function up()
    {
        \Illuminate\Support\Facades\Schema::create('categories', function (\Illuminate\Database\Schema\Blueprint $table) {
            // Replicar exactamente el esquema original
            $table->id();
            $table->string('name');
            $table->timestamps();
            // Otros campos que estaban en la migración original
        });
    }

    public function down()
    {
        \Illuminate\Support\Facades\Schema::dropIfExists('categories');
    }
}

/**
 * PASO 2: Marcar la migración original como completada en la tabla migrations
 * Ejecutar en Tinker: php artisan tinker
 * 
 * DB::table('migrations')->insert([
 *     'migration' => '2023_01_01_000000_create_categories_table',
 *     'batch' => 1 // Usar el número de lote apropiado para tu entorno
 * ]);
 */

/**
 * PASO 3: Ejecutar la nueva migración
 * Comando: php artisan migrate
 */

/**
 * PREVENCIÓN DE PROBLEMAS SIMILARES EN FUTURO
 */

/**
 * 1. Verificación de tablas dependientes
 */
class CreateProductsTableWithVerification extends \Illuminate\Database\Migrations\Migration
{
    public function up()
    {
        \Illuminate\Support\Facades\Schema::create('products', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('name');
            // Otros campos
            
            if (\Illuminate\Support\Facades\Schema::hasTable('categories')) {
                $table->foreignId('category_id')->constrained();
            } else {
                throw new \Exception('La tabla categories debe existir antes de crear la tabla products.');
            }
            
            $table->timestamps();
        });
    }

    public function down()
    {
        \Illuminate\Support\Facades\Schema::dropIfExists('products');
    }
}

/**
 * 2. Sintaxis detallada para claves foráneas
 */
class CreateProductsTableWithDetailedForeignKeys extends \Illuminate\Database\Migrations\Migration
{
    public function up()
    {
        \Illuminate\Support\Facades\Schema::create('products', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('category_id');
            $table->timestamps();
            
            $table->foreign('category_id')
                  ->references('id')
                  ->on('categories')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    public function down()
    {
        \Illuminate\Support\Facades\Schema::dropIfExists('products');
    }
}

/**
 * 3. Especificar dependencias explícitamente
 */
class CreateProductsTableWithDependencies extends \Illuminate\Database\Migrations\Migration
{
    /**
     * Dependencias de esta migración
     *
     * @var array
     */
    public $dependencies = [
        '2023_01_01_000000_create_categories_table'
    ];
    
    public function up()
    {
        \Illuminate\Support\Facades\Schema::create('products', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('category_id')->constrained();
            $table->timestamps();
        });
    }

    public function down()
    {
        \Illuminate\Support\Facades\Schema::dropIfExists('products');
    }
}

/**
 * 4. Clase base de migración con comprobación de dependencias
 */
abstract class DependentMigration extends \Illuminate\Database\Migrations\Migration
{
    public $dependencies = [];
    
    public function __construct()
    {
        // Verificar si las dependencias se han ejecutado
        $ran = \Illuminate\Support\Facades\DB::table('migrations')->pluck('migration')->toArray();
        foreach ($this->dependencies as $dependency) {
            if (!in_array($dependency, $ran)) {
                throw new \Exception("La dependencia {$dependency} no ha sido ejecutada.");
            }
        }
    }
}

// Migración que extiende de la clase base
class CreateProductsTableExtended extends DependentMigration
{
    public $dependencies = [
        '2023_01_01_000000_create_categories_table'
    ];
    
    public function up()
    {
        \Illuminate\Support\Facades\Schema::create('products', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('category_id')->constrained();
            $table->timestamps();
        });
    }

    public function down()
    {
        \Illuminate\Support\Facades\Schema::dropIfExists('products');
    }
}

/**
 * 5. Usar transacciones para migraciones atómicas
 */
class CreateProductsTableWithTransaction extends \Illuminate\Database\Migrations\Migration
{
    public function up()
    {
        \Illuminate\Support\Facades\DB::transaction(function () {
            \Illuminate\Support\Facades\Schema::create('products', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->foreignId('category_id')->constrained();
                $table->timestamps();
            });
        });
    }

    public function down()
    {
        \Illuminate\Support\Facades\Schema::dropIfExists('products');
    }
}