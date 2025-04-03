# Solution to Laravel Migration Dependency Issues

## Problem Encountered
I had two migrations with dependencies:
1. `2023_01_01_000000_create_categories_table.php` - Creates the categories table.
2. `2023_01_01_000001_create_products_table.php` - Creates the products table with a foreign key to categories.

In production, the products migration was executed without the categories migration being present, causing referential integrity issues in the database.

## Implemented Solution

### Creating a New Migration for the Categories Table

```bash
php artisan make:migration create_categories_table_fix
```

### Implementing the Migration File

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateCategoriesTableFix extends Migration
{
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        
        // Insert a default category for existing products
        DB::table('categories')->insert([
            'name' => 'Default Category',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Update existing products
        Schema::table('products', function (Blueprint $table) {
            $defaultCategoryId = DB::table('categories')->first()->id;
            DB::statement("UPDATE products SET category_id = {$defaultCategoryId} WHERE category_id IS NULL");
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->change();
            DB::statement("UPDATE products SET category_id = NULL");
        });
        
        Schema::dropIfExists('categories');
    }
}
```

### Running the Migration

```bash
php artisan migrate
```

## Prevent Future Issues

### Explicitly Declaring Dependencies in Migrations

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    public $dependencies = [
        'CreateCategoriesTable'
    ];
    
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 8, 2);
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
}
```

### Using Transactions in Complex Migrations

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ComplexMigrationWithTransaction extends Migration
{
    public function up()
    {
        DB::transaction(function () {
            Schema::create('table_one', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
            
            Schema::create('table_two', function (Blueprint $table) {
                $table->id();
                $table->foreignId('table_one_id')->constrained();
                $table->string('title');
                $table->text('content');
                $table->timestamps();
            });
            
            DB::table('table_one')->insert([
                ['name' => 'First Record', 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Second Record', 'created_at' => now(), 'updated_at' => now()]
            ]);
            
            Schema::table('table_two', function (Blueprint $table) {
                $table->index('title');
            });
        });
    }

    public function down()
    {
        DB::transaction(function () {
            Schema::dropIfExists('table_two');
            Schema::dropIfExists('table_one');
        });
    }
}
```
