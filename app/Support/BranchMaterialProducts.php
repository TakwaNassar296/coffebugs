<?php

namespace App\Support;

use App\Models\BranchMaterial;
use App\Models\BranchProduct;
use App\Models\ProductMaterialItem;

/**
 * Maps branch materials to active branch products whose recipes use each material.
 */
final class BranchMaterialProducts
{
    /**
     * @return array<int, list<array{id: int, name: string}>> material_id => unique products
     */
    public static function materialProductsMapForBranch(int $branchId): array
    {
        $productIds = BranchProduct::query()
            ->where('branch_id', $branchId)
            ->where('status', 1)
            ->pluck('product_id')
            ->unique()
            ->filter();

        if ($productIds->isEmpty()) {
            return [];
        }

        $items = ProductMaterialItem::query()
            ->whereHas('productMaterial', function ($q) use ($productIds) {
                $q->whereIn('product_id', $productIds);
            })
            ->with('productMaterial.product:id,name')
            ->get();

        $map = [];

        foreach ($items as $item) {
            $product = $item->productMaterial?->product;
            if (! $product) {
                continue;
            }
            $materialId = (int) $item->material_id;
            $map[$materialId] ??= [];
            $map[$materialId][$product->id] = [
                'id' => (int) $product->id,
                'name' => (string) $product->name,
            ];
        }

        foreach ($map as $materialId => $byProductId) {
            $map[$materialId] = array_values($byProductId);
        }

        return $map;
    }

    public static function productNamesCsvForBranchMaterial(int $branchId, int $materialId): string
    {
        $map = self::materialProductsMapForBranch($branchId);
        $products = $map[$materialId] ?? [];

        if ($products === []) {
            return '';
        }

        return collect($products)->pluck('name')->filter()->implode(', ');
    }

    public static function productNamesCsvForRecord(BranchMaterial $record): string
    {
        return self::productNamesCsvForBranchMaterial(
            (int) $record->branch_id,
            (int) $record->material_id
        );
    }
}
