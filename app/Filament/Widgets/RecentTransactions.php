<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentTransactions extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Transaksi Terbaru';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::query()
                    ->latest('date')
                    ->latest('time')
                    ->limit(5)
            )
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
                    ->label('Akun'),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('amount')
                    ->money('IDR')
                    ->label('Jumlah'),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->label('Tanggal'),
                Tables\Columns\TextColumn::make('description')
                    ->limit(30)
                    ->label('Deskripsi')
                    ->placeholder('-'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Lihat Semua')
                    ->url(fn() => route('filament.apps.resources.transactions.index'))
                    ->icon('heroicon-m-arrow-top-right-on-square'),
            ]);
    }
}
