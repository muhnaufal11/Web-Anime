<?php

namespace App\Filament\Resources\AdminEpisodeLogResource\Widgets;

use App\Models\AdminEpisodeLog;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class LogStats extends StatsOverviewWidget
{
    protected function getCards(): array
    {
        $user = auth()->user();
        $query = AdminEpisodeLog::query();

        if ($user && !$user->isSuperAdmin()) {
            $query->where('user_id', $user->id);
        }

        $pending = (clone $query)->where('status', AdminEpisodeLog::STATUS_PENDING)->sum('amount');
        $approved = (clone $query)->where('status', AdminEpisodeLog::STATUS_APPROVED)->sum('amount');
        $paid = (clone $query)->where('status', AdminEpisodeLog::STATUS_PAID)->sum('amount');
        $totalUnpaid = $pending + $approved;

        return [
            Card::make('Belum Dibayar', 'IDR ' . number_format($totalUnpaid, 0, ',', '.'))
                ->description('Pending + Approved')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),
            Card::make('Pending', 'IDR ' . number_format($pending, 0, ',', '.'))
                ->description('Menunggu review')
                ->descriptionIcon('heroicon-o-clock')
                ->color('gray'),
            Card::make('Approved', 'IDR ' . number_format($approved, 0, ',', '.'))
                ->description('Siap dibayar')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('info'),
            Card::make('Sudah Dibayar', 'IDR ' . number_format($paid, 0, ',', '.'))
                ->description('Riwayat bayar')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success'),
        ];
    }
}
