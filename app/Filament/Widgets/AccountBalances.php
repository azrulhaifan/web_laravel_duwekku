<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\TransactionResource;
use App\Models\Account;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class AccountBalances extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Saldo Akun')
            ->query(Account::active()) // Only show active accounts
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Akun')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge(),
                Tables\Columns\TextColumn::make('current_balance')
                    ->label('Saldo Saat Ini')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diperbarui')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_transactions')
                    ->label('Lihat Transaksi')
                    ->url(fn(Account $record): string => $this->getTransactionUrl($record))
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->openUrlInNewTab(),
            ])
            ->recordUrl(fn(Account $record): string => $this->getTransactionUrl($record));
    }

    protected function getTransactionUrl(Account $record): string
    {
        // Create URL with filter for this specific account
        $baseUrl = TransactionResource::getUrl('index');

        // Build the query parameters in the correct format
        $queryParams = [
            'tableFilters[account_id][values][0]' => $record->id,
        ];

        return $baseUrl . '?' . http_build_query($queryParams);
    }
}
