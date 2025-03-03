<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
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
}
