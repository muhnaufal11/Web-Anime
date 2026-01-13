<?php

namespace App\Filament\Widgets;

use App\Models\AdminEpisodeLog;
use App\Models\Anime;
use App\Models\Episode;
use App\Models\User;
use App\Models\Comment;
use App\Models\VideoServer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected function getCards(): array
    {
        $adminCount = User::admins()->count();
        $superAdminCount = User::where('role', User::ROLE_SUPERADMIN)->count();

        $cards = [
            Card::make('Total Anime', Anime::count())
                ->description('Koleksi anime')
                ->descriptionIcon('heroicon-s-film')
                ->color('primary')
                ->chart([7, 3, 4, 5, 6, 3, 5, 8]),
            Card::make('Total Episode', Episode::count())
                ->description('Episode tersedia')
                ->descriptionIcon('heroicon-s-play')
                ->color('success')
                ->chart([3, 5, 7, 9, 4, 6, 8, 10]),
            Card::make('Video Servers', VideoServer::where('is_active', true)->count())
                ->description('Server aktif')
                ->descriptionIcon('heroicon-s-server')
                ->color('warning')
                ->chart([5, 4, 6, 8, 7, 9, 6, 8]),
            Card::make('Total Users', User::count())
                ->description($adminCount . ' admin | ' . $superAdminCount . ' superadmin')
                ->descriptionIcon('heroicon-s-users')
                ->color('danger')
                ->chart([2, 3, 2, 4, 3, 5, 4, 6]),
        ];

        if (auth()->user()?->isSuperAdmin()) {
            $pendingAmount = AdminEpisodeLog::where('status', AdminEpisodeLog::STATUS_PENDING)->sum('amount');
            $cards[] = Card::make('Pending Bayaran Admin', 'Rp ' . number_format($pendingAmount, 0, ',', '.'))
                ->description('Per episode Rp 500')
                ->descriptionIcon('heroicon-s-currency-dollar')
                ->color('info')
                ->chart([5, 6, 7, 8, 6, 7, 8, 9]);
        }

        return $cards;
    }
}
