<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_values', function (Blueprint $table) {

            if (!Schema::hasColumn('product_values', 'color')) {
                $table->string('color')
                    ->nullable()
                    ->after('value');
            }

            if (!Schema::hasColumn('product_values', 'type')) {
                $table->enum('type', ['cold', 'hot'])
                    ->nullable()
                    ->after('color');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_values', function (Blueprint $table) {

            if (Schema::hasColumn('product_values', 'color')) {
                $table->dropColumn('color');
            }

            if (Schema::hasColumn('product_values', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
