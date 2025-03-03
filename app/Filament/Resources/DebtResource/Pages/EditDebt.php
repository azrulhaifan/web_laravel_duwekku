<?php

namespace App\Filament\Resources\DebtResource\Pages;

use App\Filament\Resources\DebtResource;
use App\Models\Transaction;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;

class EditDebt extends EditRecord
{
    protected static string $resource = DebtResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn() => !$this->record->is_settled),
        ];
    }

    public function mount($record): void
    {
        parent::mount($record);

        // Redirect if debt is already settled
        if ($this->record->is_settled) {
            Notification::make()
                ->title('Hutang/Piutang sudah lunas')
                ->body('Hutang/Piutang yang sudah lunas tidak dapat diedit.')
                ->danger()
                ->send();

            $this->redirect(DebtResource::getUrl('index'));
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // If marking as settled, ensure we create a settlement transaction
        if (isset($data['is_settled']) && $data['is_settled'] && empty($data['settlement_transaction_id'])) {
            // Create settlement transaction
            $debt = $this->getRecord();
            $settlementType = $debt->type === 'payable' ? 'expense' : 'income';

            $settlementDate = $data['settled_at'] ?? now()->toDateString();

            $transaction = Transaction::create([
                'account_id' => $data['account_id'],
                'type' => $settlementType,
                'amount' => $data['amount'],
                'date' => $settlementDate,
                'time' => now(),
                'description' => "Penyelesaian " . ($debt->type === 'payable' ? 'hutang' : 'piutang') . " untuk {$data['person_name']}",
            ]);

            // Set the settlement transaction ID in the form data
            $data['settlement_transaction_id'] = $transaction->id;

            // Log for debugging
            Log::info('Settlement transaction created', [
                'debt_id' => $debt->id,
                'transaction_id' => $transaction->id,
                'amount' => $data['amount']
            ]);
        }

        return $data;
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
