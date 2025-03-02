<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'category_id',
        'to_account_id',
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

    protected static function booted(): void
    {
        static::created(function (Transaction $transaction) {
            $transaction->updateAccountBalances();
        });

        static::updated(function (Transaction $transaction) {
            $transaction->updateAccountBalancesOnUpdate();
        });

        static::deleted(function (Transaction $transaction) {
            $transaction->revertAccountBalances();
        });
    }

    protected function updateAccountBalances(): void
    {
        $amount = $this->amount;
        $transactionType = match($this->type) {
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
                $newSourceBalance = $oldSourceBalance - $amount;
                
                $this->account->balanceHistories()->create([
                    'old_balance' => $oldSourceBalance,
                    'new_balance' => $newSourceBalance,
                    'amount' => -$amount,
                    'type' => 'transaction',
                    'source_type' => 'Transaction',
                    'source_id' => $this->id,
                    'description' => "Dari transaksi {$transactionType} ID #{$this->id} ke {$this->toAccount->name}",
                ]);
                
                $this->account->decrement('current_balance', $amount);
                
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

    // Similarly update the revertAccountBalances method
    protected function revertAccountBalances(): void
    {
        $amount = $this->amount;
        $transactionType = match($this->type) {
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
                $newSourceBalance = $oldSourceBalance + $amount;
                
                $this->account->balanceHistories()->create([
                    'old_balance' => $oldSourceBalance,
                    'new_balance' => $newSourceBalance,
                    'amount' => $amount,
                    'type' => 'transaction',
                    'source_type' => 'Transaction',
                    'source_id' => $this->id,
                    'description' => "Pembatalan transaksi {$transactionType} ID #{$this->id}",
                ]);
                
                $this->account->increment('current_balance', $amount);
                
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
