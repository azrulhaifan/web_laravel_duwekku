<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountResource\Pages;
use App\Filament\Resources\AccountResource\RelationManagers;
use App\Models\Account;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;

class AccountResource extends Resource
{
    // TODO AZRUL
    // PEMBULATAN DI RIWAYAT SALDO DAN LIST ACCOUNT (BALANCE) HARUS DIHAPUS (TAMPILKAN PRESISI 4 DESIMAL TANPA PEMBULATAN)

    protected static ?string $model = Account::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Pengaturan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Nama Akun'),

                Forms\Components\Select::make('type')
                    ->options([
                        'cash' => 'Uang Tunai',
                        'bank' => 'Rekening Bank',
                        'e-wallet' => 'E-Wallet',
                        'deposit' => 'Deposito',
                        'other' => 'Lainnya',
                    ])
                    ->required()
                    ->live()
                    ->label('Tipe Akun'),

                Forms\Components\Select::make('currency_code')
                    ->options([
                        'IDR' => 'Rupiah (IDR)',
                        'USD' => 'US Dollar (USD)',
                        'CUSTOM' => 'Unit Kustom',
                    ])
                    ->default('IDR')
                    ->required()
                    ->live()
                    ->label('Mata Uang'),

                Forms\Components\TextInput::make('custom_unit')
                    ->visible(fn($get) => $get('currency_code') === 'CUSTOM')
                    ->required(fn($get) => $get('currency_code') === 'CUSTOM')
                    ->label('Satuan Kustom (contoh: gram)'),

                Forms\Components\TextInput::make('initial_balance')
                    ->required()
                    ->mask(RawJs::make('$money($input,`.`,`,`,4)'))
                    ->stripCharacters(',')
                    ->numeric()
                    ->label(
                        fn($get) =>
                        $get('currency_code') === 'CUSTOM'
                            ? 'Saldo Awal (' . ($get('custom_unit') ?: 'satuan kustom') . ')'
                            : 'Saldo Awal'
                    )
                    ->helperText(
                        fn($get) =>
                        $get('currency_code') === 'CUSTOM'
                            ? 'Jumlah awal dalam satuan kustom saat akun dibuat'
                            : 'Saldo awal dalam mata uang'
                    )
                    ->disabled(fn(?Account $record) => $record !== null && $record->exists),

                // Add this new field to display current balance for non-custom currencies
                Forms\Components\TextInput::make('current_balance')
                    ->label('Saldo Saat Ini')
                    ->mask(RawJs::make('$money($input,`.`,`,`,4)'))
                    ->stripCharacters(',')
                    ->visible(fn(?Account $record) => $record !== null && $record->exists && $record->currency_code !== 'CUSTOM')
                    ->formatStateUsing(function ($state, $record) {
                        if (!$record) return null;
                        return $state;
                    }),

                // Add the adjustment reason field
                Forms\Components\Textarea::make('adjustment_reason')
                    ->label('Alasan Penyesuaian Saldo')
                    ->visible(fn(?Account $record) => $record !== null && $record->exists && $record->currency_code !== 'CUSTOM')
                    ->placeholder('Masukkan alasan penyesuaian saldo (opsional)')
                    ->maxLength(255),

                Forms\Components\TextInput::make('custom_unit_amount')
                    ->visible(fn($get) => $get('currency_code') === 'CUSTOM')
                    ->numeric()
                    ->mask(RawJs::make('$money($input,`.`,`,`,4)'))
                    ->stripCharacters(',')
                    ->label('Jumlah Saat Ini dalam Satuan Kustom')
                    ->helperText('Jumlah terkini yang Anda miliki dalam satuan kustom'),

                Forms\Components\Select::make('estimated_currency_code')
                    ->options([
                        'IDR' => 'Rupiah (IDR)',
                        'USD' => 'US Dollar (USD)',
                    ])
                    ->visible(fn($get) => $get('currency_code') === 'CUSTOM')
                    ->default('IDR')
                    ->label('Mata Uang Taksiran'),

                Forms\Components\TextInput::make('estimated_balance')
                    ->visible(fn($get) => $get('currency_code') === 'CUSTOM')
                    ->numeric()
                    ->mask(RawJs::make('$money($input,`.`,`,`,4)'))
                    ->stripCharacters(',')
                    ->label('Nilai Taksiran'),

                Forms\Components\ColorPicker::make('color')
                    ->label('Warna'),

                Forms\Components\TextInput::make('icon')
                    ->label('Icon'),

                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->label('Deskripsi'),

                Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->default(true)
                    ->label('Akun Aktif'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Nama'),

                Tables\Columns\TextColumn::make('description')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return "-";
                        }

                        if (strlen($state) <= 25) {
                            return $state;
                        }

                        return '...' . substr($state, -25);
                    })
                    ->label('Deskripsi'),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'cash' => 'Uang Tunai',
                        'bank' => 'Rekening Bank',
                        'e-wallet' => 'E-Wallet',
                        'deposit' => 'Deposito',
                        'other' => 'Lainnya',
                        default => $state,
                    })
                    ->label('Tipe'),

                Tables\Columns\TextColumn::make('current_balance')
                    ->formatStateUsing(function ($state, $record) {
                        if (!$record) return null;

                        if ($record->currency_code === 'CUSTOM') {
                            return number_format($record->current_balance, 4) . ' ' . $record->custom_unit;
                        } else {
                            $symbol = match ($record->currency_code) {
                                'USD' => '$',
                                'IDR' => 'Rp',
                                default => ($record->currency_code ?? 'IDR') . ' ',
                            };

                            $decimals = $record->currency_code === 'IDR' ? 0 : 2;
                            return $symbol . ' ' . number_format($record->current_balance, $decimals, ',', '.');
                        }
                    })
                    ->sortable()
                    ->label('Saldo Saat Ini'),

                Tables\Columns\TextColumn::make('estimated_balance')
                    ->money(fn($record) => $record && $record->estimated_currency_code ? $record->estimated_currency_code : 'IDR')
                    ->visible(fn($record) => $record && $record->currency_code === 'CUSTOM')
                    ->sortable()
                    ->label('Nilai Taksiran'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable()
                    ->label('Status'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Terakhir Diperbarui'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'cash' => 'Uang Tunai',
                        'bank' => 'Rekening Bank',
                        'e-wallet' => 'E-Wallet',
                        'deposit' => 'Deposito',
                        'other' => 'Lainnya',
                    ])
                    ->label('Tipe Akun'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Akun')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif')
                    ->placeholder('Semua'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BalanceHistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }
}
