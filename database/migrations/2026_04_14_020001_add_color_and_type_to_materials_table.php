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
        Schema::table('materials', function (Blueprint $table) {

            if (!Schema::hasColumn('materials', 'color')) {
                $table->string('color')
                    ->nullable()
                    ->after('name');
            }

            if (!Schema::hasColumn('materials', 'type')) {
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
        Schema::table('materials', function (Blueprint $table) {

            if (Schema::hasColumn('materials', 'color')) {
                $table->dropColumn('color');
            }

            if (Schema::hasColumn('materials', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
