<?php

namespace App\Filament\Resources\DebtResource\Pages;

use App\Filament\Resources\DebtResource;
use App\Models\Transaction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewDebt extends ViewRecord
{
    protected static string $resource = DebtResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Hutang/Piutang')
                    ->schema([
                        Infolists\Components\TextEntry::make('type')
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
                        Infolists\Components\TextEntry::make('person_name')
                            ->label('Nama Orang/Pihak'),
                        Infolists\Components\TextEntry::make('account.name')
                            ->label('Akun'),
                        Infolists\Components\TextEntry::make('amount')
                            ->money('IDR')
                            ->label('Jumlah'),
                        Infolists\Components\TextEntry::make('date')
                            ->date()
                            ->label('Tanggal'),
                        Infolists\Components\TextEntry::make('due_date')
                            ->date()
                            ->label('Tanggal Jatuh Tempo'),
                        Infolists\Components\TextEntry::make('description')
                            ->label('Deskripsi')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Status Pelunasan')
                    ->schema([
                        Infolists\Components\IconEntry::make('is_settled')
                            ->boolean()
                            ->label('Status Lunas'),
                        Infolists\Components\TextEntry::make('settled_at')
                            ->date()
                            ->label('Tanggal Pelunasan')
                            ->visible(fn($record) => $record->is_settled),
                    ]),

                Infolists\Components\Section::make('Transaksi Terkait')
                    ->schema([
                        Infolists\Components\TextEntry::make('transaction.id')
                            ->label('ID Transaksi Awal')
                            ->url(fn($record) => $record->transaction_id
                                ? "/admin/transactions/{$record->transaction_id}"
                                : null)
                            ->color('primary')
                            ->visible(fn($record) => $record->transaction_id !== null),

                        Infolists\Components\TextEntry::make('transaction.description')
                            ->label('Deskripsi Transaksi Awal')
                            ->visible(fn($record) => $record->transaction_id !== null),

                        Infolists\Components\TextEntry::make('transaction.date')
                            ->date()
                            ->label('Tanggal Transaksi Awal')
                            ->visible(fn($record) => $record->transaction_id !== null),

                        Infolists\Components\TextEntry::make('settlementTransaction.id')
                            ->label('ID Transaksi Pelunasan')
                            ->url(fn($record) => $record->settlement_transaction_id
                                ? "/admin/transactions/{$record->settlement_transaction_id}"
                                : null)
                            ->color('success')
                            ->visible(fn($record) => $record->settlement_transaction_id !== null),

                        Infolists\Components\TextEntry::make('settlementTransaction.description')
                            ->label('Deskripsi Transaksi Pelunasan')
                            ->visible(fn($record) => $record->settlement_transaction_id !== null),

                        Infolists\Components\TextEntry::make('settlementTransaction.date')
                            ->date()
                            ->label('Tanggal Transaksi Pelunasan')
                            ->visible(fn($record) => $record->settlement_transaction_id !== null),
                    ])
                    ->columns(2),
            ]);
    }
}
