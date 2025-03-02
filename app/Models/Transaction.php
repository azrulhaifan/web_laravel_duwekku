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

        switch ($this->type) {
            case 'income':
                $this->account->increment('current_balance', $amount);
                break;

            case 'expense':
                $this->account->decrement('current_balance', $amount);
                break;

            case 'transfer':
                $this->account->decrement('current_balance', $amount);
                $this->toAccount->increment('current_balance', $amount);
                break;
        }
    }

    protected function updateAccountBalancesOnUpdate(): void
    {
        // Jika tipe transaksi berubah, kita perlu mengembalikan saldo lama dan menerapkan saldo baru
        if ($this->wasChanged('type') || $this->wasChanged('account_id') || $this->wasChanged('to_account_id')) {
            // Revert old transaction
            $oldType = $this->getOriginal('type');
            $oldAmount = $this->getOriginal('amount');
            $oldAccountId = $this->getOriginal('account_id');
            $oldToAccountId = $this->getOriginal('to_account_id');

            $oldAccount = Account::find($oldAccountId);
            $oldToAccount = $oldToAccountId ? Account::find($oldToAccountId) : null;

            if ($oldAccount) {
                switch ($oldType) {
                    case 'income':
                        $oldAccount->decrement('current_balance', $oldAmount);
                        break;
                    case 'expense':
                        $oldAccount->increment('current_balance', $oldAmount);
                        break;
                    case 'transfer':
                        if ($oldToAccount) {
                            $oldAccount->increment('current_balance', $oldAmount);
                            $oldToAccount->decrement('current_balance', $oldAmount);
                        }
                        break;
                }
            }

            // Apply new transaction
            $this->updateAccountBalances();
        }
        // Jika hanya jumlah yang berubah, kita cukup menerapkan selisih
        elseif ($this->wasChanged('amount')) {
            $oldAmount = $this->getOriginal('amount');
            $newAmount = $this->amount;
            $difference = $newAmount - $oldAmount;

            switch ($this->type) {
                case 'income':
                    $this->account->increment('current_balance', $difference);
                    break;
                case 'expense':
                    $this->account->decrement('current_balance', $difference);
                    break;
                case 'transfer':
                    $this->account->decrement('current_balance', $difference);
                    $this->toAccount->increment('current_balance', $difference);
                    break;
            }
        }
    }
}
