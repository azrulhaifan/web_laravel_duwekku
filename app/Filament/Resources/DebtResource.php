<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DebtResource\Pages;
use App\Models\Debt;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DebtResource extends Resource
{
    protected static ?string $model = Debt::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?string $navigationLabel = 'Hutang & Piutang';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->options([
                        'receivable' => 'Piutang (Uang akan kembali)',
                        'payable' => 'Hutang (Uang harus dibayar)',
                    ])
                    ->required()
                    ->label('Tipe'),
                Forms\Components\Select::make('account_id')
                    ->relationship('account', 'name')
                    ->required()
                    ->preload()
                    ->label('Akun'),
                Forms\Components\TextInput::make('person_name')
                    ->required()
                    ->maxLength(255)
                    ->label('Nama Orang/Pihak'),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->label('Jumlah'),
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->default(now())
                    ->label('Tanggal'),
                Forms\Components\DatePicker::make('due_date')
                    ->label('Tanggal Jatuh Tempo'),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->label('Deskripsi/Alasan'),
                Forms\Components\Toggle::make('is_settled')
                    ->label('Sudah Diselesaikan')
                    ->live(),
                Forms\Components\DatePicker::make('settled_date')
                    ->label('Tanggal Penyelesaian')
                    ->visible(fn ($get) => $get('is_settled')),
                Forms\Components\Select::make('settlement_transaction_id')
                    ->options(fn () => Transaction::orderBy('date', 'desc')->pluck('description', 'id')->toArray())
                    ->searchable()
                    ->preload()
                    ->label('Transaksi Penyelesaian')
                    ->visible(fn ($get) => $get('is_settled')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'receivable' => 'success',
                        'payable' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'receivable' => 'Piutang',
                        'payable' => 'Hutang',
                        default => $state,
                    })
                    ->label('Tipe'),
                Tables\Columns\TextColumn::make('person_name')
                    ->searchable()
                    ->label('Nama Orang/Pihak'),
                Tables\Columns\TextColumn::make('account.name')
                    ->sortable()
                    ->label('Akun'),
                Tables\Columns\TextColumn::make('amount')
                    ->money('IDR')
                    ->sortable()
                    ->label('Jumlah'),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable()
                    ->label('Tanggal'),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->label('Jatuh Tempo'),
                Tables\Columns\IconColumn::make('is_settled')
                    ->boolean()
                    ->label('Lunas'),
                Tables\Columns\TextColumn::make('settled_date')
                    ->date()
                    ->sortable()
                    ->label('Tanggal Pelunasan')
                    ->visible(fn ($livewire) => $livewire->getTableFilterState('is_settled') === true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'receivable' => 'Piutang',
                        'payable' => 'Hutang',
                    ])
                    ->label('Tipe'),
                Tables\Filters\SelectFilter::make('account_id')
                    ->relationship('account', 'name')
                    ->label('Akun'),
                Tables\Filters\TernaryFilter::make('is_settled')
                    ->label('Status Pelunasan'),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('date_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn ($query, $date) => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_until'],
                                fn ($query, $date) => $query->whereDate('date', '<=', $date),
                            );
                    })
                    ->label('Rentang Tanggal'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('settle')
                    ->label('Lunasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\DatePicker::make('settled_date')
                            ->required()
                            ->default(now())
                            ->label('Tanggal Pelunasan'),
                        Forms\Components\Select::make('settlement_transaction_id')
                            ->options(fn () => Transaction::orderBy('date', 'desc')->pluck('description', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->label('Transaksi Pelunasan'),
                    ])
                    ->action(function (Debt $record, array $data) {
                        $record->update([
                            'is_settled' => true,
                            'settled_date' => $data['settled_date'],
                            'settlement_transaction_id' => $data['settlement_transaction_id'],
                        ]);
                    })
                    ->visible(fn (Debt $record) => !$record->is_settled),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDebts::route('/'),
            'create' => Pages\CreateDebt::route('/create'),
            'edit' => Pages\EditDebt::route('/{record}/edit'),
        ];
    }
}
