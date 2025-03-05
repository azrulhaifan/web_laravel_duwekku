<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DebtResource\Pages;
use App\Models\Debt;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;

class DebtResource extends Resource
{
    // TODO AZRUL
    // PASTIKAN PERHITUNGAN DEBT SUDAH BENAR
    // UNTUK TRANSAKSI EXPANSE (PENGURANGAN AMOUNT), REJECT / TOLAK SUBMIT JIKA BALANCE NYA KURANG

    protected static ?string $model = Debt::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?string $navigationLabel = 'Hutang & Piutang';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Make sure all Select components have proper options with non-null labels
                Forms\Components\Select::make('account_id')
                    ->relationship('account', 'name', function ($query) {
                        // Only show active accounts AND exclude custom currency accounts
                        return $query->active()->where(function ($query) {
                            $query->where('currency_code', '!=', 'CUSTOM')
                                ->orWhereNull('currency_code');
                        });
                    })
                    ->required()
                    ->label('Akun'),

                Forms\Components\Select::make('type')
                    ->options([
                        'payable' => 'Hutang (Uang harus dibayar)',
                        'receivable' => 'Piutang (Uang akan kembali)',
                    ])
                    ->required()
                    ->label('Tipe Hutang/Piutang'),

                Forms\Components\TextInput::make('person_name')
                    ->required()
                    ->maxLength(255)
                    ->label('Nama Orang/Pihak'),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->mask(RawJs::make('$money($input,`.`,`,`,4)'))
                    ->stripCharacters(',')
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
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        if ($state) {
                            $set('settled_at', now()->toDateString()); // Store as date string
                        } else {
                            $set('settled_at', null);
                        }
                    }),

                Forms\Components\DatePicker::make('settled_at')
                    ->label('Tanggal Penyelesaian')
                    ->visible(fn($get) => $get('is_settled'))
                    ->required(fn($get) => $get('is_settled')),

                // Replace the Select component with a Hidden field and a Placeholder to display info
                Forms\Components\Hidden::make('settlement_transaction_id')
                    ->visible(fn($get) => $get('is_settled')),

                Forms\Components\Placeholder::make('settlement_transaction_info')
                    ->label('Transaksi Penyelesaian')
                    ->content('Transaksi penyelesaian akan dibuat otomatis')
                    ->visible(fn($get) => $get('is_settled')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc') // This ensures sorting by date in descending order
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'receivable' => 'success',
                        'payable' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
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
                // In the table columns section, update the settled_date column to settled_at
                Tables\Columns\TextColumn::make('settled_at')
                    ->date()
                    ->sortable()
                    ->label('Tanggal Pelunasan')
                    ->visible(fn($livewire) => $livewire->getTableFilterState('is_settled') === true),
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
                                fn($query, $date) => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_until'],
                                fn($query, $date) => $query->whereDate('date', '<=', $date),
                            );
                    })
                    ->label('Rentang Tanggal'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn(Debt $record) => !$record->is_settled),
                Tables\Actions\Action::make('settle')
                    ->label('Lunasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\DatePicker::make('settled_at')
                            ->required()
                            ->default(now())
                            ->label('Tanggal Pelunasan'),

                        // Remove the Select component for settlement_transaction_id
                        Forms\Components\Placeholder::make('settlement_transaction_info')
                            ->label('Transaksi Penyelesaian')
                            ->content('Transaksi penyelesaian akan dibuat otomatis'),
                    ])
                    ->action(function (Debt $record, array $data) {
                        // Set a flag to prevent duplicate account history entries
                        request()->merge(['_transaction_update' => true]);

                        // Create settlement transaction automatically
                        $settlementType = $record->type === 'payable' ? 'expense' : 'income';

                        $transaction = Transaction::create([
                            'account_id' => $record->account_id,
                            'type' => $settlementType,
                            'amount' => $record->amount,
                            'date' => $data['settled_at'],
                            'time' => now(),
                            'description' => "Penyelesaian " . ($record->type === 'payable' ? 'hutang' : 'piutang') . " untuk {$record->person_name}",
                        ]);

                        $record->update([
                            'is_settled' => true,
                            'settled_at' => $data['settled_at'],
                            'settlement_transaction_id' => $transaction->id,
                        ]);
                    })
                    ->visible(fn(Debt $record) => !$record->is_settled),
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
            'view' => Pages\ViewDebt::route('/{record}'),
        ];
    }
}
