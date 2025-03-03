<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AccountBalances;
use App\Filament\Widgets\DashboardStats;
use App\Filament\Widgets\DebtSummary;
use App\Filament\Widgets\MonthlyIncomeExpense;
use App\Filament\Widgets\RecentTransactions;
use App\Filament\Widgets\UpcomingDebts;
use App\Models\Account;
use App\Models\Debt;
use App\Models\Transaction;
use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget\StatsOverviewWidget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.dashboard';

    protected static ?int $navigationSort = -2;

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Dashboard Keuangan';

    public function getHeaderWidgets(): array
    {
        return [
            DashboardStats::class,
            AccountBalances::class,
            DebtSummary::class,
        ];
    }

    public function getFooterWidgets(): array
    {
        return [
            RecentTransactions::class,
            MonthlyIncomeExpense::class,
            UpcomingDebts::class,
        ];
    }
}