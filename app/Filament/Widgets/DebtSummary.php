<?php

namespace App\Filament\Widgets;

use App\Models\Debt;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DebtSummary extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        // Get total payable (hutang)
        $totalPayable = Debt::where('type', 'payable')
            ->where('is_settled', false)
            ->sum('amount');

        // Get total receivable (piutang)
        $totalReceivable = Debt::where('type', 'receivable')
            ->where('is_settled', false)
            ->sum('amount');

        // Get count of upcoming due debts
        $upcomingDueCount = Debt::where('is_settled', false)
            ->whereNotNull('due_date')
            ->where('due_date', '<=', now()->addDays(7))
            ->count();

        return [
            Stat::make('Total Hutang (Harus Dibayar)', 'Rp ' . number_format($totalPayable, 0, ',', '.'))
                ->description('Belum lunas')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('danger'),

            Stat::make('Total Piutang (Uang Kembali)', 'Rp ' . number_format($totalReceivable, 0, ',', '.'))
                ->description('Belum lunas')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('success'),

            Stat::make('Jatuh Tempo Dalam 7 Hari', $upcomingDueCount)
                ->description('Hutang & Piutang')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
