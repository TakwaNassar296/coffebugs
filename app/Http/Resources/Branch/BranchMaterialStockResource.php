<?php

namespace App\Http\Resources\Branch;

use App\Support\MaterialUnit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * JSON shape for branch_materials rows (internal/external inventory API).
 * Named distinctly from Filament's MaterialResource to avoid deploy/path confusion.
 */
class BranchMaterialStockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentQuantity = $this->current_quantity ?? 0;
        $minLimit = $this->min_limit ?? 0;
        $maxLimit = $this->max_limit ?? 0;

        $status = $this->calculateStockStatus($currentQuantity, $minLimit, $maxLimit);

        $unit = $this->material?->unit;
        $materialId = $this->material?->id;
        $productsByMaterial = $request->attributes->get('material_products_by_material_id', []);

        return [
            'id' => $this->material?->id,
            'name' => $this->material?->name,
            'code' => $this->material?->code,
            'unit' => $unit,
            'unit_label' => MaterialUnit::label($unit),
            'quantity_in_stock' => $this->quantity_in_stock,
            'quantity_in_stock_display' => MaterialUnit::formatQuantity($this->quantity_in_stock, $unit),
            'current_quantity' => $currentQuantity,
            'current_quantity_display' => MaterialUnit::formatQuantity($currentQuantity, $unit),
            'stock' => $status,
            'category_name' => $this->material->category->name ?? null,
            'category_id' => $this->material->category_id ?? null,
            'status' => $this->getStatusLabel($status),
            'image' => $this->material && $this->material->image
                ? asset('storage/'.$this->material->image)
                : asset('images/default.png'),
            'max' => $maxLimit,
            'min' => $minLimit,
            'products' => $materialId !== null
                ? ($productsByMaterial[$materialId] ?? [])
                : [],
        ];
    }

    /**
     * Calculate stock status based on current quantity and limits
     */
    private function calculateStockStatus(float $currentQuantity, float $minLimit, float $maxLimit): string
    {
        if ($currentQuantity <= 0) {
            return 'out_of_stock';
        }

        if ($minLimit > 0 && $currentQuantity < $minLimit) {
            return 'low_stock';
        }

        if ($minLimit > 0 && $currentQuantity >= $minLimit) {
            return 'good';
        }

        $quantityInStock = $this->quantity_in_stock ?? 0;
        if ($quantityInStock > 0) {
            $percentage = ($currentQuantity / $quantityInStock) * 100;
            if ($percentage < 10) {
                return 'low_stock';
            }
        }

        return 'good';
    }

    /**
     * Get human-readable status label
     */
    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'out_of_stock' => __('admin.out_of_stock'),
            'low_stock' => __('admin.low_stock'),
            'good' => __('admin.good'),
            default => __('admin.good'),
        };
    }
}
