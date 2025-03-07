<?php

namespace App\Filament\Resources\DebtResource\Pages;

use App\Filament\Resources\DebtResource;
use App\Models\Account;
use App\Models\Transaction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateDebt extends CreateRecord
{
    protected static string $resource = DebtResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Check if there's enough balance for expense transactions
        // For debts, 'receivable' type will create an expense transaction
        if (isset($data['type']) && $data['type'] === 'receivable') {
            $account = Account::find($data['account_id']);
            if ($account && $account->current_balance < $data['amount']) {
                // Not enough balance, show error notification
                Notification::make()
                    ->title('Saldo tidak cukup')
                    ->body("Saldo akun {$account->name} tidak mencukupi untuk mencatat piutang ini.")
                    ->danger()
                    ->persistent()
                    ->send();

                $this->halt();
                
                return $data;
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $debt = $this->record;

        // Add flag to prevent duplicate account history entries
        request()->merge(['_transaction_update' => true]);

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
