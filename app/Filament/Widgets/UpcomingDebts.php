<?php

namespace App\Filament\Widgets;

use App\Models\Debt;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UpcomingDebts extends BaseWidget
{
    protected static ?string $heading = 'Hutang & Piutang Jatuh Tempo';

    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Debt::query()
                    ->where('is_settled', false)
                    ->whereNotNull('due_date')
                    ->where('due_date', '<=', now()->addDays(7))
                    ->orderBy('due_date')
            )
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
                    ->label('Nama Orang/Pihak'),
                Tables\Columns\TextColumn::make('amount')
                    ->money('IDR')
                    ->label('Jumlah'),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->label('Jatuh Tempo'),
                Tables\Columns\TextColumn::make('description')
                    ->limit(30)
                    ->label('Deskripsi')
                    ->placeholder('-'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Lihat Semua')
                    ->url(fn() => route('filament.apps.resources.debts.index', [
                        'tableFilters[is_settled][values][0]' => false,
                    ]))
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->openUrlInNewTab(),
            ]);
    }
}
