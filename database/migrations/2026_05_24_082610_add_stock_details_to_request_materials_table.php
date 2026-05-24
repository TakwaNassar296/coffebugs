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
       

        Schema::table('request_materials', function (Blueprint $table) {
            $table->decimal('stock_at_request', 10, 2)->nullable();
            $table->decimal('min_stock_at_request', 10, 2)->nullable();
            $table->decimal('max_stock_at_request', 10, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       
        Schema::table('request_materials', function (Blueprint $table) {
            $table->dropColumn(['stock_at_request', 'min_stock_at_request', 'max_stock_at_request']);
        });
    }
};
