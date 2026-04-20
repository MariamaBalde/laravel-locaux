<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'vendeur_id',
        'category_id',
        'name',
        'description',
        'price',
        'stock',
        'images',
        'weight',
        'is_active',
        'featured',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'weight' => 'decimal:2',
        'images' => 'array',
        'is_active' => 'boolean',
        'featured' => 'boolean',
    ];

    public function vendeur()
    {
        return $this->belongsTo(Vendeur::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function canEdit(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->isVendeur()) {
            return $this->vendeur_id === $user->vendeur->id;
        }

        return false;
    }

    // Accessors
    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 2).' FCFA';
    }

    public function getMainImageAttribute()
    {
        return $this->images[0] ?? null;
    }
}
