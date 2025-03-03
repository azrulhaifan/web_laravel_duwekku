<?php

namespace App\Filament\Resources\DebtResource\Pages;

use App\Filament\Resources\DebtResource;
use App\Models\Transaction;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDebt extends EditRecord
{
    protected static string $resource = DebtResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $debt = $this->getRecord();

        // Check if this debt has a related transaction
        if ($debt->transaction_id) {
            $transaction = Transaction::find($debt->transaction_id);

            if ($transaction) {
                // Update the related transaction with debt data
                $transaction->update([
                    'account_id' => $debt->account_id,
                    'amount' => $debt->amount,
                    'date' => $debt->date,
                    'description' => $debt->description,
                ]);

                // If the transaction has a different amount than the debt,
                // we need to update account balances
                if ($transaction->getOriginal('amount') != $debt->amount) {
                    // Get original data before our update
                    $originalData = $transaction->getOriginal();

                    // Update account balances based on the changes
                    $transaction->updateAccountBalancesOnUpdate($originalData);
                }
            }
        }
    }
}
