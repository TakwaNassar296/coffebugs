<?php

use App\Models\BranchProduct;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $dupKeys = BranchProduct::query()
            ->selectRaw('branch_id, product_id')
            ->groupBy('branch_id', 'product_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($dupKeys as $row) {
            $rows = BranchProduct::query()
                ->where('branch_id', $row->branch_id)
                ->where('product_id', $row->product_id)
                ->orderBy('id')
                ->get();

            $keeper = $rows->first();
            $keeper->amount = $rows->sum(fn ($r) => (float) $r->amount);
            $keeper->status = $rows->contains(fn ($r) => (bool) $r->status);
            $keeper->save();

            $rows->skip(1)->each->delete();
        }

        Schema::table('branch_product', function (Blueprint $table) {
            $table->unique(['branch_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('branch_product', function (Blueprint $table) {
            $table->dropUnique(['branch_id', 'product_id']);
        });
    }
};
