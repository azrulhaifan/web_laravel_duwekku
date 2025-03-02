<?php

namespace App\Filament\Resources\AccountResource\RelationManagers;

use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BalanceHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'balanceHistories';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $title = 'Riwayat Saldo';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('description')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Tanggal'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'adjustment' => 'warning',
                        'transaction' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'adjustment' => 'Penyesuaian',
                        'transaction' => 'Transaksi',
                        default => $state,
                    })
                    ->label('Tipe'),
                Tables\Columns\TextColumn::make('old_balance')
                    ->money('IDR')
                    ->label('Saldo Lama'),
                Tables\Columns\TextColumn::make('new_balance')
                    ->money('IDR')
                    ->label('Saldo Baru'),
                Tables\Columns\TextColumn::make('amount')
                    ->money('IDR')
                    ->color(fn($record) => $record->amount >= 0 ? 'success' : 'danger')
                    ->label('Jumlah'),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->label('Deskripsi'),
                Tables\Columns\TextColumn::make('source_id')
                    ->label('Transaksi')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record && $record->source_type === 'Transaction' && $state) {
                            $transaction = Transaction::find($state);
                            if ($transaction) {
                                return "#{$state}";
                            }
                        }
                        return '';
                    })
                    ->url(function ($record) {
                        if ($record && $record->source_type === 'Transaction' && $record->source_id) {
                            $transaction = Transaction::find($record->source_id);
                            if ($transaction) {
                                return route('filament.admin.resources.transactions.edit', ['record' => $record->source_id]);
                            }
                        }
                        return null;
                    })
                    ->openUrlInNewTab()
                    ->visible(fn($record) => $record && $record->source_type === 'Transaction' && $record->source_id),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'adjustment' => 'Penyesuaian',
                        'transaction' => 'Transaksi',
                    ])
                    ->label('Tipe'),
            ])
            ->headerActions([
                // No actions needed for history
            ])
            ->actions([
                // No actions needed for history
            ])
            ->bulkActions([
                // No bulk actions needed for history
            ])
            ->defaultSort('created_at', 'desc');
    }
}
