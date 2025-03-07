<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use App\Models\Account;
use App\Models\Transaction;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function mount($record): void
    {
        parent::mount($record);

        // Check if this transaction is related to a debt
        $hasDebt = $this->record->debts()->count() > 0;
        $isSettlement = $this->record->settlementDebts()->count() > 0;

        // Redirect if transaction is related to a debt
        if ($hasDebt || $isSettlement) {
            $message = $hasDebt
                ? 'Transaksi ini terkait dengan hutang/piutang dan tidak dapat diedit.'
                : 'Transaksi ini merupakan transaksi pelunasan hutang/piutang dan tidak dapat diedit.';

            Notification::make()
                ->title('Transaksi tidak dapat diedit')
                ->body($message)
                ->danger()
                ->send();

            $this->redirect(TransactionResource::getUrl('index'));
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Add flag to prevent duplicate account history entries
        request()->merge(['_transaction_update' => true]);

        // Check if there's enough balance for expense transactions
        if (isset($data['type']) && $data['type'] === 'expense') {
            $transaction = $this->getRecord();
            $account = Account::find($data['account_id']);

            // Calculate the additional amount being spent (if any)
            $additionalAmount = 0;
            if ($transaction->account_id == $data['account_id']) {
                // Same account, just check the difference
                $additionalAmount = $data['amount'] - $transaction->amount;
            } else {
                // Different account, need to check the full amount
                $additionalAmount = $data['amount'];
            }

            // Only check if we're increasing the expense amount
            if ($additionalAmount > 0 && $account && $account->current_balance < $additionalAmount) {
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
}
