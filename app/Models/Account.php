<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'initial_balance',
        'current_balance',
        'currency',
        'icon',
        'color',
        'description',
        'is_active',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Account $account) {
            $account->current_balance = $account->initial_balance;
        });
    }

    public function balanceHistories(): HasMany
    {
        return $this->hasMany(AccountBalanceHistory::class);
    }
}
