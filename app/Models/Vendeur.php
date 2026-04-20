<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendeur extends Model
{
    /** @use HasFactory<\Database\Factories\VendeurFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shop_name',
        'description',
        'verified',
        'rating',
        'total_sales',
    ];

    protected $casts = [
        'verified' => 'boolean',
        'rating' => 'decimal:2',
        'total_sales' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }
}
