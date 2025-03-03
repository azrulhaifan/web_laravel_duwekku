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

        static::updating(function (Account $account) {
            // Only record history if current_balance is changed manually
            // (not through transactions which handle their own history)
            if (
                $account->isDirty('current_balance') &&
                !app()->runningInConsole() &&
                request()->has('balance_adjustment_description')
            ) {

                $oldBalance = $account->getOriginal('current_balance');
                $newBalance = $account->current_balance;
                $amount = $newBalance - $oldBalance;

                $account->balanceHistories()->create([
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance,
                    'amount' => $amount,
                    'type' => 'adjustment',
                    'source_type' => 'Manual',
                    'description' => request('balance_adjustment_description') ?? 'Manual balance adjustment',
                ]);
            }
        });
    }

    public function balanceHistories(): HasMany
    {
        return $this->hasMany(AccountBalanceHistory::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Increment the account balance by the specified amount
     *
     * @param float $amount
     * @return void
     */
    public function incrementBalance($amount): void
    {
        $this->increment('current_balance', $amount);
    }

    /**
     * Decrement the account balance by the specified amount
     *
     * @param float $amount
     * @return void
     */
    public function decrementBalance($amount): void
    {
        $this->decrement('current_balance', $amount);
    }

    // Add a scope for active accounts
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
