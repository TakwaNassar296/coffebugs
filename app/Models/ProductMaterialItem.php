<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductMaterialItem extends Model
{
    protected $fillable = [
        'product_material_id',
        'material_id',
        'quantity_used',
        'unit',
    ];

    public function productMaterial()
    {
        return $this->belongsTo(ProductsMaterial::class, 'product_material_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function productOption()
    {
        return $this->belongsTo(ProductOption::class);
    }
}