<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use App\Models\Debt;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

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
