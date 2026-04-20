<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'provider',
        'label',
        'account_number',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    protected $hidden = [
        'account_number',
    ];

    protected $appends = [
        'account_masked',
    ];

    public function getAccountMaskedAttribute(): ?string
    {
        if (! $this->account_number) {
            return null;
        }

        $length = strlen($this->account_number);
        if ($length <= 4) {
            return $this->account_number;
        }

        return str_repeat('*', $length - 4).substr($this->account_number, -4);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
