<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use App\Models\Debt;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Check if this transaction has a debt record
        $transaction = $this->getRecord();
        $debt = Debt::where('transaction_id', $transaction->id)->first();
        
        if ($debt) {
            $data['create_debt'] = true;
            $data['person_name'] = $debt->person_name;
            $data['due_date'] = $debt->due_date;
        }
        
        return $data;
    }

    protected function afterSave(): void
    {
        $transaction = $this->getRecord();
        $data = $this->data;
        
        // Get existing debt record if any
        $debt = Debt::where('transaction_id', $transaction->id)->first();
        
        if (isset($data['create_debt']) && $data['create_debt'] && in_array($transaction->type, ['income', 'expense'])) {
            // Determine debt type based on transaction type
            $debtType = $transaction->type === 'expense' ? 'receivable' : 'payable';
            
            if ($debt) {
                // Update existing debt record
                $debt->update([
                    'account_id' => $transaction->account_id,
                    'type' => $debtType,
                    'person_name' => $data['person_name'] ?? $debt->person_name,
                    'amount' => $transaction->amount,
                    'date' => $transaction->date,
                    'due_date' => $data['due_date'] ?? $debt->due_date,
                    'description' => $transaction->description,
                ]);
            } else {
                // Create new debt record
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
        } elseif ($debt && (!isset($data['create_debt']) || !$data['create_debt'])) {
            // If debt record exists but create_debt is unchecked, delete the debt record
            $debt->delete();
        }
    }
}
