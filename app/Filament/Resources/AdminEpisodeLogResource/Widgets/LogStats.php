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

        // For non-superadmin, show only their own data
        if ($user && !$user->isSuperAdmin()) {
            $query->where('user_id', $user->id);
        }

        $pending = (clone $query)->where('status', AdminEpisodeLog::STATUS_PENDING)->sum('amount');
        $approved = (clone $query)->where('status', AdminEpisodeLog::STATUS_APPROVED)->sum('amount');
        $paid = (clone $query)->where('status', AdminEpisodeLog::STATUS_PAID)->sum('amount');
        $totalUnpaid = $pending + $approved;

        $cards = [
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

        // Tambah info limit & rollover untuk admin (non-superadmin)
        if ($user && $user->isAdmin() && !$user->isSuperAdmin()) {
            $calc = $user->calculateMonthlyPayment(now()->year, now()->month);
            $limit = $user->getMonthlyLimit();
            $rollover = $user->rollover_balance ?? 0;
            $daysUntilPayday = $user->getDaysUntilPayday();
            
            // Tambah card limit info
            $cards[] = Card::make('Limit Bulanan', $limit ? 'IDR ' . number_format($limit, 0, ',', '.') : '∞ Unlimited')
                ->description($user->getAdminLevelLabel())
                ->descriptionIcon('heroicon-o-shield-check')
                ->color('primary');
            
            // Card yang bisa dicairkan bulan ini
            $cards[] = Card::make('Dapat Dicairkan', 'IDR ' . number_format($calc['payable'], 0, ',', '.'))
                ->description($daysUntilPayday == 0 ? '🎉 Hari ini gajian!' : $daysUntilPayday . ' hari lagi (tgl 25)')
                ->descriptionIcon('heroicon-o-cash')
                ->color('success');
            
            // Rollover
            if ($rollover > 0 || $calc['rollover_to_next'] > 0) {
                $cards[] = Card::make('Rollover', 'IDR ' . number_format($rollover + $calc['rollover_to_next'], 0, ',', '.'))
                    ->description('Dibayar bulan depan')
                    ->descriptionIcon('heroicon-o-arrow-right')
                    ->color('danger');
            }
        }

        // Superadmin: tampilkan summary semua admin
        if ($user && $user->isSuperAdmin()) {
            $totalAdmins = \App\Models\User::admins()
                ->where('role', '!=', \App\Models\User::ROLE_SUPERADMIN)
                ->count();
            
            $totalPayable = 0;
            $totalRollover = 0;
            
            $admins = \App\Models\User::admins()
                ->where('role', '!=', \App\Models\User::ROLE_SUPERADMIN)
                ->get();
            
            foreach ($admins as $admin) {
                $calc = $admin->calculateMonthlyPayment(now()->year, now()->month);
                $totalPayable += $calc['payable'];
                $totalRollover += $calc['rollover_to_next'];
            }
            
            $daysUntilPayday = now()->day <= 25 ? 25 - now()->day : now()->daysInMonth - now()->day + 25;
            
            $cards[] = Card::make('Total Dapat Dibayar', 'IDR ' . number_format($totalPayable, 0, ',', '.'))
                ->description($totalAdmins . ' admin | ' . ($daysUntilPayday == 0 ? 'Hari ini!' : $daysUntilPayday . ' hari lagi'))
                ->descriptionIcon('heroicon-o-cash')
                ->color('success');
            
            if ($totalRollover > 0) {
                $cards[] = Card::make('Total Rollover', 'IDR ' . number_format($totalRollover, 0, ',', '.'))
                    ->description('Rollover ke bulan depan')
                    ->descriptionIcon('heroicon-o-arrow-right')
                    ->color('danger');
            }
        }

        return $cards;
    }
}
