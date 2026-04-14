<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    /** @use HasFactory<\Database\Factories\SiteSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image',
        'delivery_charge',
        'images',
        "tax_percentage",
        "free_delivery_minimum",
        'driver_finance',
        'related_product_discount',
        'max_range',
        'app_link_google_play',
        'app_link_app_store',
        'app_store_version',
        'google_play_version',
        'text_cart',
        "text_order",
        'money_per_point'
    ];


    protected $casts = ['images'=>'array'];
    
    
    public function features()
    {
        return $this->hasMany(SiteSettingFeature::class);
    }


}
