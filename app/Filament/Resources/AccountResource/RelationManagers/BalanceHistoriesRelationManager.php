<?php

namespace App\Filament\Resources\AccountResource\RelationManagers;

use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

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
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Tanggal'),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'transaction' => 'Transaksi',
                        'adjustment' => 'Penyesuaian',
                        'estimation' => 'Taksiran',
                        default => $state,
                    })
                    ->label('Tipe'),

                Tables\Columns\TextColumn::make('old_balance')
                    ->formatStateUsing(function ($state, $record) {
                        $account = $record->account;

                        if ($record->is_custom_unit && $account && $account->custom_unit) {
                            return number_format($state, 4) . ' ' . $account->custom_unit;
                        } elseif ($record->is_estimation && $account && $account->estimated_currency_code) {
                            return $this->formatMoney($state, $account->estimated_currency_code);
                        } else {
                            return $this->formatMoney($state, $account->currency_code ?? 'IDR');
                        }
                    })
                    ->label('Saldo Lama'),

                Tables\Columns\TextColumn::make('new_balance')
                    ->formatStateUsing(function ($state, $record) {
                        $account = $record->account;

                        if ($record->is_custom_unit && $account && $account->custom_unit) {
                            return number_format($state, 4) . ' ' . $account->custom_unit;
                        } elseif ($record->is_estimation && $account && $account->estimated_currency_code) {
                            return $this->formatMoney($state, $account->estimated_currency_code);
                        } else {
                            return $this->formatMoney($state, $account->currency_code ?? 'IDR');
                        }
                    })
                    ->label('Saldo Baru'),

                Tables\Columns\TextColumn::make('amount')
                    ->formatStateUsing(function ($state, $record) {
                        $account = $record->account;

                        if ($record->is_custom_unit && $account && $account->custom_unit) {
                            $prefix = $state > 0 ? '+' : '';
                            return $prefix . number_format($state, 4) . ' ' . $account->custom_unit;
                        } elseif ($record->is_estimation && $account && $account->estimated_currency_code) {
                            $prefix = $state > 0 ? '+' : '';
                            return $prefix . $this->formatMoney($state, $account->estimated_currency_code);
                        } else {
                            $prefix = $state > 0 ? '+' : '';
                            return $prefix . $this->formatMoney($state, $account->currency_code ?? 'IDR');
                        }
                    })
                    ->label('Jumlah'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi'),
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

    /**
     * Format a number as money with currency
     */
    private function formatMoney($amount, $currency = 'IDR'): string
    {
        $symbol = match ($currency) {
            'USD' => '$',
            'IDR' => 'Rp',
            default => $currency . ' ',
        };

        $decimals = $currency === 'IDR' ? 2 : 4;
        $formattedAmount = number_format($amount, $decimals, '.', ',');

        return $symbol . ' ' . $formattedAmount;
    }
}
