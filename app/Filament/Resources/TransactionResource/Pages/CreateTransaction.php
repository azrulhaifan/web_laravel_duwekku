<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use App\Models\Account;
use App\Models\Debt;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Add flag to prevent duplicate account history entries
        request()->merge(['_transaction_update' => true]);

        // Check if there's enough balance for expense transactions
        if (isset($data['type']) && $data['type'] === 'expense') {
            $account = Account::find($data['account_id']);
            if ($account && $account->current_balance < $data['amount']) {
                // Not enough balance, show error notification and halt
                Notification::make()
                    ->title('Saldo tidak cukup')
                    ->body("Saldo akun {$account->name} tidak mencukupi untuk transaksi pengeluaran ini.")
                    ->danger()
                    ->send();

                $this->halt();

                return $data;
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $transaction = $this->record;
        $data = $this->data;

        // Create debt record if requested
        if (isset($data['create_debt']) && $data['create_debt'] && in_array($transaction->type, ['income', 'expense'])) {
            // Determine debt type based on transaction type
            $debtType = $transaction->type === 'expense' ? 'receivable' : 'payable';

            Debt::create([
                'account_id' => $transaction->account_id,
                'transaction_id' => $transaction->id,
                'type' => $debtType,
                'person_name' => $data['person_name'] ?? 'Unknown',
                'amount' => $transaction->amount,
                'date' => $transaction->date,
                'due_date' => $data['due_date'] ?? null,
                'description' => $transaction->description,
                'is_settled' => false,
            ]);
        }
    }
}
