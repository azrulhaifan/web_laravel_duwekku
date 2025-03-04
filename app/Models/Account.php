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
        'currency_code',
        'custom_unit',
        'custom_unit_amount',
        'estimated_balance',
        'estimated_currency_code',
        'icon',
        'color',
        'description',
        'is_active',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'custom_unit_amount' => 'decimal:4',
        'estimated_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Account $account) {
            $account->current_balance = $account->initial_balance;

            // For custom currency accounts, initialize custom_unit_amount if not set
            if ($account->currency_code === 'CUSTOM' && $account->custom_unit_amount === null) {
                $account->custom_unit_amount = $account->initial_balance;
            }
        });

        static::updating(function (Account $account) {
            // Handle regular currency accounts
            if (
                $account->isDirty('current_balance') &&
                !app()->runningInConsole() &&
                request()->has('balance_adjustment_description') &&
                $account->currency_code !== 'CUSTOM'
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

            // Handle custom currency accounts
            if (
                $account->currency_code === 'CUSTOM' &&
                ($account->isDirty('custom_unit_amount') || $account->isDirty('estimated_balance')) &&
                !app()->runningInConsole()
            ) {
                $description = request('balance_adjustment_description') ?? 'Manual balance adjustment';

                // Track custom unit amount changes
                if ($account->isDirty('custom_unit_amount')) {
                    $oldUnitAmount = $account->getOriginal('custom_unit_amount') ?? 0;
                    $newUnitAmount = $account->custom_unit_amount;
                    $unitChange = $newUnitAmount - $oldUnitAmount;

                    $account->balanceHistories()->create([
                        'old_balance' => $oldUnitAmount,
                        'new_balance' => $newUnitAmount,
                        'amount' => $unitChange,
                        'type' => 'adjustment',
                        'source_type' => 'Manual',
                        'description' => $description . " ({$account->custom_unit})",
                        'is_custom_unit' => true,
                    ]);

                    // Also update current_balance to keep it in sync
                    $account->current_balance = $newUnitAmount;
                }

                // Track estimated balance changes
                if ($account->isDirty('estimated_balance')) {
                    $oldEstimatedBalance = $account->getOriginal('estimated_balance') ?? 0;
                    $newEstimatedBalance = $account->estimated_balance;
                    $estimatedChange = $newEstimatedBalance - $oldEstimatedBalance;

                    $account->balanceHistories()->create([
                        'old_balance' => $oldEstimatedBalance,
                        'new_balance' => $newEstimatedBalance,
                        'amount' => $estimatedChange,
                        'type' => 'estimation',
                        'source_type' => 'Manual',
                        'description' => "Perubahan nilai taksiran ({$account->estimated_currency_code})",
                        'is_estimation' => true,
                    ]);
                }
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
