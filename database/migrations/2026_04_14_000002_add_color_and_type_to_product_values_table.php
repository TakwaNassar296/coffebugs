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
            $table->string('color')->nullable()->after('value');
            $table->enum('type', ['cold', 'hot'])->nullable()->after('color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_values', function (Blueprint $table) {
            $table->dropColumn(['color', 'type']);
        });
    }
};
