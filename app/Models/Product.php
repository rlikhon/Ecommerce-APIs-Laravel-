<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\ProductImage;
use App\Models\ProductSize;
use App\Models\Size;

class Product extends Model
{
    use HasFactory;

    protected $appends = ['image_url'];
    
    public function getImageUrlAttribute()
    {
        return $this->image ? asset('/uploads/products/small/' . $this->image) : null;
    }

    public function product_images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function product_sizes()
    {
        return $this->hasMany(ProductSize::class);
    }

     /**
     * Define the many-to-many relationship with Size.
     */
    public function sizes(): BelongsToMany 
    {
        // 1st arg: Target Model
        // 2nd arg: Your actual table name (including the typo if it exists in DB)
        return $this->belongsToMany(Size::class, 'product_sizes', 'product_id', 'size_id');
    }
}
