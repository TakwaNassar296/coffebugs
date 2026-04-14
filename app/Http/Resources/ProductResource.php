<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\Api\ProductOtionResource;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\ProductOptionResource;

class ProductResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $isFavourite = false;
        $favouriteId = null;

        if (auth('user')->check()) {
            $favourite = $this->favourites()
                ->where('user_id', auth('user')->id())
                ->when($request->has('branch_id'), function ($query) use ($request) {
                    $query->where('branch_id', $request->branch_id);
                })
                ->first();

            if ($favourite) {
                $isFavourite = true;
                $favouriteId = $favourite->id;
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'main_image' => $this->main_image ? url("storage/{$this->main_image}") : null,
            'images' => is_array($this->image)
                ? array_map(fn($img) => url("storage/{$img}"), $this->image)
                : [],
            'title' => $this->title,
            'description' => $this->description,
            'price' => (int) $this->price,
            'price_after_coupon' => $this->price_after_discount !== null ? (float) $this->price_after_discount : null,
            //'price_after_coupon' => $this->calcPrice(),
            'points' => $this->points,
            'stars' => $this->stars,
            'rating_avg' => $this->averageRating(),
            'revies_count' => $this->reviewsCount(),
            // 'delivery_time' => $this->delivery_time,
            'delivery_time' => $this->delivery_product_time,
            'start_minutes' => $this->stat_minutes,
            'end_minutes' => $this->end_minutes,
            'total_sales' => $this->total_sales,
            'remaining_quantity' => $this->remaining_quantity,
            'is_options' => isset($this->options_count)
                ? $this->options_count > 0
                : ($this->relationLoaded('options') ? $this->options->isNotEmpty() : $this->options()->exists()),
            'is_favourite' => $isFavourite,
            'favourite_id' => $favouriteId,
            'category' => new CategoryResource($this->category),
        ];
    }
    
}
