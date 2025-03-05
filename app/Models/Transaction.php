<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'category_id',
        'to_account_id',
        'transfer_fee',
        'type',
        'amount',
        'date',
        'time',
        'description',
        'attachment',
        'is_recurring',
        'recurring_type',
        'recurring_day',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'time' => 'datetime',
        'is_recurring' => 'boolean',
        'transfer_fee' => 'decimal:4',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }

    /**
     * Get the debt record associated with the transaction.
     */
    public function debt(): HasOne
    {
        return $this->hasOne(Debt::class);
    }

    public function debts()
    {
        return $this->hasMany(Debt::class, 'transaction_id');
    }

    public function settlementDebts()
    {
        return $this->hasMany(Debt::class, 'settlement_transaction_id');
    }

    protected static function booted(): void
    {
        static::created(function (Transaction $transaction) {
            $transaction->updateAccountBalances();
        });

        static::updated(function (Transaction $transaction) {
            // Get the original attributes before the update
            $originalData = $transaction->getOriginal();
            $transaction->updateAccountBalancesOnUpdate($originalData);
        });

        static::deleted(function (Transaction $transaction) {
            $transaction->revertAccountBalances();
        });
    }

    protected function updateAccountBalances(): void
    {
        $amount = $this->amount;
        $transactionType = match ($this->type) {
            'income' => 'Pemasukan',
            'expense' => 'Pengeluaran',
            'transfer' => 'Transfer',
            default => $this->type
        };

        switch ($this->type) {
            case 'income':
                $oldBalance = $this->account->current_balance;
                $newBalance = $oldBalance + $amount;

                // Create balance history with proper description and relation
                $this->account->balanceHistories()->create([
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance,
                    'amount' => $amount,
                    'type' => 'transaction',
                    'source_type' => 'Transaction',
                    'source_id' => $this->id,
                    'description' => "Dari transaksi {$transactionType} ID #{$this->id}",
                ]);

                $this->account->increment('current_balance', $amount);
                break;

            case 'expense':
                $oldBalance = $this->account->current_balance;
                $newBalance = $oldBalance - $amount;

                // Create balance history with proper description and relation
                $this->account->balanceHistories()->create([
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance,
                    'amount' => -$amount,
                    'type' => 'transaction',
                    'source_type' => 'Transaction',
                    'source_id' => $this->id,
                    'description' => "Dari transaksi {$transactionType} ID #{$this->id}",
                ]);

                $this->account->decrement('current_balance', $amount);
                break;

            case 'transfer':
                // Source account
                $oldSourceBalance = $this->account->current_balance;
                $totalDeduction = $amount + ($this->transfer_fee ?? 0);
                $newSourceBalance = $oldSourceBalance - $totalDeduction;

                $this->account->balanceHistories()->create([
                    'old_balance' => $oldSourceBalance,
                    'new_balance' => $newSourceBalance,
                    'amount' => -$totalDeduction,
                    'type' => 'transaction',
                    'source_type' => 'Transaction',
                    'source_id' => $this->id,
                    'description' => "Dari transaksi {$transactionType} ID #{$this->id} ke {$this->toAccount->name}" .
                        ($this->transfer_fee > 0 ? " (termasuk biaya transfer " . number_format($this->transfer_fee, 0, ',', '.') . ")" : ""),
                ]);

                $this->account->decrement('current_balance', $totalDeduction);

                // Destination account
                $oldDestBalance = $this->toAccount->current_balance;
                $newDestBalance = $oldDestBalance + $amount;

                $this->toAccount->balanceHistories()->create([
                    'old_balance' => $oldDestBalance,
                    'new_balance' => $newDestBalance,
                    'amount' => $amount,
                    'type' => 'transaction',
                    'source_type' => 'Transaction',
                    'source_id' => $this->id,
                    'description' => "Dari transaksi {$transactionType} ID #{$this->id} dari {$this->account->name}",
                ]);

                $this->toAccount->increment('current_balance', $amount);
                break;
        }
    }

    /**
     * Update account balances when a transaction is updated
     */
    public function updateAccountBalancesOnUpdate(array $originalData): void
    {
        // Set a flag to indicate this is a transaction update
        request()->merge(['_transaction_update' => true]);

        $originalType = $originalData['type'] ?? null;
        $originalAccountId = $originalData['account_id'] ?? null;
        $originalToAccountId = $originalData['to_account_id'] ?? null;
        $originalAmount = $originalData['amount'] ?? 0;
        $originalTransferFee = $originalData['transfer_fee'] ?? 0;

        // First, reverse the effect of the original transaction
        if ($originalType && $originalAccountId) {
            $originalAccount = Account::find($originalAccountId);

            if ($originalAccount) {
                if ($originalType === 'income') {
                    $originalAccount->decrementBalance($originalAmount);
                } elseif ($originalType === 'expense') {
                    $originalAccount->incrementBalance($originalAmount);
                } elseif ($originalType === 'transfer' && $originalToAccountId) {
                    // For transfers, include the original transfer fee when reverting
                    $originalTotalDeduction = $originalAmount + $originalTransferFee;
                    $originalAccount->incrementBalance($originalTotalDeduction);

                    $originalToAccount = Account::find($originalToAccountId);
                    if ($originalToAccount) {
                        $originalToAccount->decrementBalance($originalAmount);
                    }
                }
            }
        }

        // Then apply the effect of the updated transaction
        $this->updateAccountBalances();
    }

    // Similarly update the revertAccountBalances method
    protected function revertAccountBalances(): void
    {
        $amount = $this->amount;
        $transactionType = match ($this->type) {
            'income' => 'Pemasukan',
            'expense' => 'Pengeluaran',
            'transfer' => 'Transfer',
            default => $this->type
        };

        switch ($this->type) {
            case 'income':
                $oldBalance = $this->account->current_balance;
                $newBalance = $oldBalance - $amount;

                $this->account->balanceHistories()->create([
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance,
                    'amount' => -$amount,
                    'type' => 'transaction',
                    'source_type' => 'Transaction',
                    'source_id' => $this->id,
                    'description' => "Pembatalan transaksi {$transactionType} ID #{$this->id}",
                ]);

                $this->account->decrement('current_balance', $amount);
                break;

            case 'expense':
                $oldBalance = $this->account->current_balance;
                $newBalance = $oldBalance + $amount;

                $this->account->balanceHistories()->create([
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance,
                    'amount' => $amount,
                    'type' => 'transaction',
                    'source_type' => 'Transaction',
                    'source_id' => $this->id,
                    'description' => "Pembatalan transaksi {$transactionType} ID #{$this->id}",
                ]);

                $this->account->increment('current_balance', $amount);
                break;

            case 'transfer':
                // Source account
                $oldSourceBalance = $this->account->current_balance;
                $totalDeduction = $amount + ($this->transfer_fee ?? 0);
                $newSourceBalance = $oldSourceBalance + $totalDeduction;

                $this->account->balanceHistories()->create([
                    'old_balance' => $oldSourceBalance,
                    'new_balance' => $newSourceBalance,
                    'amount' => $totalDeduction,
                    'type' => 'transaction',
                    'source_type' => 'Transaction',
                    'source_id' => $this->id,
                    'description' => "Pembatalan transaksi {$transactionType} ID #{$this->id}" .
                        ($this->transfer_fee > 0 ? " (termasuk biaya transfer " . number_format($this->transfer_fee, 0, ',', '.') . ")" : ""),
                ]);

                $this->account->increment('current_balance', $totalDeduction);

                // Destination account
                $oldDestBalance = $this->toAccount->current_balance;
                $newDestBalance = $oldDestBalance - $amount;

                $this->toAccount->balanceHistories()->create([
                    'old_balance' => $oldDestBalance,
                    'new_balance' => $newDestBalance,
                    'amount' => -$amount,
                    'type' => 'transaction',
                    'source_type' => 'Transaction',
                    'source_id' => $this->id,
                    'description' => "Pembatalan transaksi {$transactionType} ID #{$this->id}",
                ]);

                $this->toAccount->decrement('current_balance', $amount);
                break;
        }
    }
}
