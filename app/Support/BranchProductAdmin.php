<?php

namespace App\Support;

use App\Models\BranchMaterial;
use App\Models\BranchProduct;
use App\Models\ProductsMaterial;
use Illuminate\Support\Facades\DB;

final class BranchProductAdmin
{
    /**
     * Create a branch product or add amount to an existing row (same branch + product).
     * Applies branch material stock changes for the added quantity only (merge) or full amount (new).
     */
    public static function createOrMerge(array $data): BranchProduct
    {
        $branchId = (int) ($data['branch_id'] ?? 0);
        $productId = (int) ($data['product_id'] ?? 0);

        $existing = BranchProduct::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $delta = (float) ($data['amount'] ?? 0);
            $existing->amount = (float) $existing->amount + $delta;
            if (array_key_exists('status', $data)) {
                $existing->status = (bool) $data['status'];
            }
            $existing->save();

            self::applyMaterialsForQuantity($existing, $delta);

            return $existing;
        }

        $record = BranchProduct::create($data);
        self::applyMaterialsForQuantity($record, (float) ($record->amount ?? 0));

        return $record;
    }

    /**
     * @param  array<int|string>  $productIds
     */
    public static function createManyForBranch(int $branchId, array $productIds, float $amountPerProduct, bool $status = true): int
    {
        $ids = array_values(array_unique(array_map('intval', $productIds)));
        if ($ids === [] || $branchId <= 0) {
            return 0;
        }

        $processed = 0;

        DB::transaction(function () use ($branchId, $ids, $amountPerProduct, $status, &$processed): void {
            foreach ($ids as $productId) {
                if ($productId <= 0) {
                    continue;
                }

                self::createOrMerge([
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'amount' => $amountPerProduct,
                    'status' => $status,
                ]);
                $processed++;
            }
        });

        return $processed;
    }

    public static function applyMaterialsForQuantity(BranchProduct $branchProduct, float $productAmount): void
    {
        if (! $branchProduct->branch_id || ! $branchProduct->product_id) {
            return;
        }

        if ($productAmount <= 0) {
            return;
        }

        $productMaterials = ProductsMaterial::where('product_id', $branchProduct->product_id)
            ->with('items.material')
            ->get();

        if ($productMaterials->isEmpty()) {
            return;
        }

        $combinedMaterials = [];

        foreach ($productMaterials as $pm) {
            foreach ($pm->items as $item) {
                $key = $item->material_id.'_'.$item->unit;

                if (! isset($combinedMaterials[$key])) {
                    $combinedMaterials[$key] = [
                        'material_id' => $item->material_id,
                        'unit' => $item->unit,
                        'total_quantity' => 0,
                    ];
                }

                $combinedMaterials[$key]['total_quantity'] += (float) $item->quantity_used;
            }
        }

        DB::beginTransaction();
        try {
            foreach ($combinedMaterials as $combined) {
                $totalQuantity = $combined['total_quantity'] * $productAmount;

                $branchMaterial = BranchMaterial::firstOrNew([
                    'branch_id' => $branchProduct->branch_id,
                    'material_id' => $combined['material_id'],
                ]);

                if ($branchMaterial->exists) {
                    $branchMaterial->quantity_in_stock += $totalQuantity;
                    $branchMaterial->current_quantity += $totalQuantity;
                } else {
                    $branchMaterial->quantity_in_stock = $totalQuantity;
                    $branchMaterial->current_quantity = $totalQuantity;
                }

                $branchMaterial->unit = $combined['unit'];
                $branchMaterial->save();
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
