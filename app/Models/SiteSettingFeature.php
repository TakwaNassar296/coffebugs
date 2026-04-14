<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteSettingFeature extends Model
{
    use HasFactory;

    protected $table = 'site_setting_features';

    protected $fillable = [
        'site_setting_id',
        'title',
        'description',
        'image',
    ];

    public function siteSetting()
    {
        return $this->belongsTo(SiteSetting::class, 'site_setting_id');
    }
}