<?php

namespace App\Filament\Widgets;

use App\Models\Account;
use App\Models\Debt;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class DashboardStats extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        // Get total balance across all accounts
        $totalBalance = Account::sum('current_balance');
        
        // Get income and expense for current month
        $currentMonth = Carbon::now()->startOfMonth();
        $monthlyIncome = Transaction::where('type', 'income')
            ->whereMonth('date', $currentMonth->month)
            ->whereYear('date', $currentMonth->year)
            ->sum('amount');
            
        $monthlyExpense = Transaction::where('type', 'expense')
            ->whereMonth('date', $currentMonth->month)
            ->whereYear('date', $currentMonth->year)
            ->sum('amount');
            
        // Calculate monthly savings
        $monthlySavings = $monthlyIncome - $monthlyExpense;
        $savingsColor = $monthlySavings >= 0 ? 'success' : 'danger';

        return [
            Stat::make('Total Saldo', 'Rp ' . number_format($totalBalance, 0, ',', '.'))
                ->description('Semua akun')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('success'),
                
            Stat::make('Pemasukan Bulan Ini', 'Rp ' . number_format($monthlyIncome, 0, ',', '.'))
                ->description(Carbon::now()->format('F Y'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
                
            Stat::make('Pengeluaran Bulan Ini', 'Rp ' . number_format($monthlyExpense, 0, ',', '.'))
                ->description(Carbon::now()->format('F Y'))
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
                
            Stat::make('Tabungan Bulan Ini', 'Rp ' . number_format($monthlySavings, 0, ',', '.'))
                ->description($monthlySavings >= 0 ? 'Positif' : 'Negatif')
                ->descriptionIcon($monthlySavings >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($savingsColor),
        ];
    }
}