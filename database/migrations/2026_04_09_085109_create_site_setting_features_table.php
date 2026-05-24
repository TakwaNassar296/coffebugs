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
        if (!Schema::hasTable('site_setting_features')) {

            Schema::create('site_setting_features', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_setting_id')->constrained('site_settings')->onDelete('cascade');
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->string('image')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('site_setting_features')) {
            Schema::dropIfExists('site_setting_features');
        }
    }
};
