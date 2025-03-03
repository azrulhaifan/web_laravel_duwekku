<?php

namespace App\Filament\Widgets;

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
                    ->url(fn(Account $record): string => route('filament.apps.resources.transactions.index', [
                        'tableFilters[account_id][value]' => $record->id,
                    ]))
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->openUrlInNewTab(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_transactions')
                    ->label('Lihat Transaksi')
                    ->url(fn(Account $record): string => route('filament.apps.resources.transactions.index', [
                        'tableFilters[account_id][value]' => $record->id,
                    ]))
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->openUrlInNewTab(),
            ]);
    }
}
