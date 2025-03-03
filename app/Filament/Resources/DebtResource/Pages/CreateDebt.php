<?php

namespace App\Filament\Resources\DebtResource\Pages;

use App\Filament\Resources\DebtResource;
use App\Models\Transaction;
use Filament\Resources\Pages\CreateRecord;

class CreateDebt extends CreateRecord
{
    protected static string $resource = DebtResource::class;

    protected function afterCreate(): void
    {
        $debt = $this->record;

        // Create related transaction to update account balance
        $transactionType = $debt->type === 'payable' ? 'income' : 'expense';

        // Create the transaction
        $transaction = Transaction::create([
            'account_id' => $debt->account_id,
            'type' => $transactionType,
            'amount' => $debt->amount,
            'date' => $debt->date,
            'time' => now(),
            'description' => $debt->description ?? "Transaksi dari hutang/piutang: {$debt->person_name}",
        ]);

        // Link the transaction to the debt
        $debt->update([
            'transaction_id' => $transaction->id
        ]);
    }
}
