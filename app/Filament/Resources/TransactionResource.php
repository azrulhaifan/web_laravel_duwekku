<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->options([
                        'income' => 'Pemasukan',
                        'expense' => 'Pengeluaran',
                        'transfer' => 'Transfer',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn(Forms\Set $set) => $set('category_id', null))
                    ->label('Tipe Transaksi'),
                Forms\Components\Select::make('account_id')
                    ->relationship('account', 'name', function ($query) {
                        // Only show active accounts AND exclude custom currency accounts
                        return $query->active()->where(function ($query) {
                            $query->where('currency_code', '!=', 'CUSTOM')
                                ->orWhereNull('currency_code');
                        });
                    })
                    ->required()
                    ->preload()
                    ->label('Akun')
                    ->live(),
                Forms\Components\Select::make('category_id')
                    ->relationship('category', 'name', function (Builder $query, $get) {
                        $type = $get('type');
                        if ($type && $type !== 'transfer') {
                            $query->where('type', $type);
                        }
                    })
                    ->visible(fn($get) => $get('type') !== 'transfer')
                    ->preload()
                    ->label('Kategori'),
                Forms\Components\Select::make('to_account_id')
                    ->options(function ($get) {
                        $accountId = $get('account_id');

                        // Only show active accounts AND exclude custom currency accounts
                        $accounts = Account::active()
                            ->where(function ($query) {
                                $query->where('currency_code', '!=', 'CUSTOM')
                                    ->orWhereNull('currency_code');
                            });

                        // Exclude the source account from destination options
                        if ($accountId) {
                            $accounts->where('id', '!=', $accountId);
                        }

                        return $accounts->pluck('name', 'id')->toArray();
                    })
                    ->visible(fn($get) => $get('type') === 'transfer')
                    ->required(fn($get) => $get('type') === 'transfer')
                    ->label('Akun Tujuan'),
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
                Forms\Components\TimePicker::make('time')
                    ->seconds(false)
                    ->default(now())
                    ->label('Waktu'),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->label('Deskripsi'),
                Forms\Components\FileUpload::make('attachment')
                    ->disk('private')
                    ->directory('attachments')
                    ->visibility('private')
                    // ->multiple()
                    ->openable()
                    ->downloadable()
                    ->acceptedFileTypes([
                        'image/jpeg',
                        'image/png',
                        'image/gif',
                        'image/webp',
                        'application/pdf',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'text/csv'
                    ])
                    ->label('Lampiran'),
                // Tambahkan opsi untuk mencatat hutang/piutang
                Forms\Components\Toggle::make('create_debt')
                    ->label('Catat sebagai Hutang/Piutang')
                    ->live()
                    ->visible(fn($get) => in_array($get('type'), ['income', 'expense'])),
                Forms\Components\Hidden::make('debt_type')
                    ->default(fn($get) => $get('type') === 'expense' ? 'receivable' : 'payable')
                    ->live(),
                Forms\Components\Placeholder::make('debt_type_display')
                    ->label('Tipe Hutang/Piutang')
                    ->content(function ($get) {
                        $type = $get('type');
                        if ($type === 'expense') {
                            return 'Piutang (Uang akan kembali)';
                        } else {
                            return 'Hutang (Uang harus dibayar)';
                        }
                    })
                    ->visible(fn($get) => $get('create_debt') && in_array($get('type'), ['income', 'expense'])),
                Forms\Components\TextInput::make('person_name')
                    ->maxLength(255)
                    ->visible(fn($get) => $get('create_debt') && in_array($get('type'), ['income', 'expense']))
                    ->label('Nama Orang/Pihak'),
                Forms\Components\DatePicker::make('due_date')
                    ->visible(fn($get) => $get('create_debt') && in_array($get('type'), ['income', 'expense']))
                    ->label('Tanggal Jatuh Tempo'),
                // Menyembunyikan field transaksi berulang
                Forms\Components\Toggle::make('is_recurring')
                    ->label('Transaksi Berulang')
                    ->live()
                    ->hidden(),
                Forms\Components\Select::make('recurring_type')
                    ->options([
                        'daily' => 'Harian',
                        'weekly' => 'Mingguan',
                        'monthly' => 'Bulanan',
                        'yearly' => 'Tahunan',
                    ])
                    ->hidden()
                    ->label('Tipe Pengulangan'),
                Forms\Components\TextInput::make('recurring_day')
                    ->numeric()
                    ->hidden()
                    ->label('Hari Pengulangan')
                    ->visible(fn($get) => $get('is_recurring'))
                    ->required(fn($get) => $get('is_recurring'))
                    ->label('Hari Pengulangan'),

                Forms\Components\TextInput::make('transfer_fee')
                    ->numeric()
                    ->mask(RawJs::make('$money($input,`.`,`,`,2)'))
                    ->stripCharacters(',')
                    ->visible(fn($get) => $get('type') === 'transfer')
                    ->required(fn($get) => $get('type') === 'transfer')
                    ->label('Biaya Transfer')
                    ->helperText('Biaya yang dikenakan untuk melakukan transfer'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->defaultSort('time', 'desc') // Add time sorting as secondary sort
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'income' => 'success',
                        'expense' => 'danger',
                        'transfer' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'income' => 'Pemasukan',
                        'expense' => 'Pengeluaran',
                        'transfer' => 'Transfer',
                        default => $state,
                    })
                    ->label('Tipe'),
                Tables\Columns\TextColumn::make('account.name')
                    ->sortable()
                    ->label('Akun'),
                Tables\Columns\TextColumn::make('category.name')
                    ->sortable()
                    ->label('Kategori')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('toAccount.name')
                    ->sortable()
                    ->label('Akun Tujuan')
                    ->visible(fn($state, $livewire) => $livewire instanceof Pages\ListTransactions &&
                        ($livewire->getTableFilterState('type') ?? null) === 'transfer'),
                Tables\Columns\TextColumn::make('amount')
                    ->money('IDR')
                    ->sortable()
                    ->label('Jumlah'),
                Tables\Columns\TextColumn::make('date')
                    ->formatStateUsing(fn(Transaction $record): string =>
                    $record->date->format('d M Y') .
                        ($record->time ? ' ' . $record->time->format('H:i') : ''))
                    ->sortable()
                    ->label('Tanggal'),
                Tables\Columns\IconColumn::make('is_recurring')
                    ->boolean()
                    ->label('Berulang')
                    ->hidden(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Dibuat Pada'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Diperbarui Pada'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'income' => 'Pemasukan',
                        'expense' => 'Pengeluaran',
                        'transfer' => 'Transfer',
                    ])
                    ->label('Tipe'),
                Tables\Filters\SelectFilter::make('account_id')
                    ->relationship('account', 'name')
                    ->label('Akun'),
                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->label('Kategori'),
                // Fix for DateRangeFilter - using the correct namespace
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('date_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    })
                    ->label('Rentang Tanggal'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(function (Transaction $record) {
                        // Check if this transaction is related to a debt
                        $hasDebt = $record->debts()->count() > 0;
                        $isSettlement = $record->settlementDebts()->count() > 0;

                        // Don't allow editing if it's related to a debt or is a settlement transaction
                        return !($hasDebt || $isSettlement);
                    }),
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
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
