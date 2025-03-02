<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\AccountBalanceHistory;

class AccountObserver
{
    /**
     * Handle the Account "creating" event.
     */
    public function creating(Account $account): void
    {
        $account->current_balance = $account->initial_balance;
    }

    /**
     * Handle the Account "updating" event.
     */
    public function updating(Account $account): void
    {
        // Jika saldo berubah, catat history
        if ($account->isDirty('current_balance')) {
            $oldBalance = $account->getOriginal('current_balance');
            $newBalance = $account->current_balance;
            $amount = $newBalance - $oldBalance;

            AccountBalanceHistory::create([
                'account_id' => $account->id,
                'old_balance' => $oldBalance,
                'new_balance' => $newBalance,
                'amount' => $amount,
                'type' => 'adjustment',
                'source_type' => 'Manual',
                'description' => request('balance_adjustment_description') ?? 'Manual balance adjustment',
            ]);
        }
    }
}
