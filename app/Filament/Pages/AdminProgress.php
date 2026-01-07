<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\AdminProgressService;
use Filament\Pages\Page;
use Filament\Pages\Actions\Action;
use Filament\Notifications\Notification;

class AdminProgress extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Admin Performance';
    protected static ?int $navigationSort = 10;
    protected static string $view = 'filament.pages.admin-progress';

    public static function getNavigationGroup(): ?string
    {
        // Superadmin sees it in SUPERADMIN group, others see it in ADMIN group
        return auth()->user()?->isSuperAdmin() ? 'SUPERADMIN' : 'ADMIN';
    }

    public static function canAccess(): bool
    {
        // Both superadmin and regular admins can access
        $user = auth()->user();
        return $user && ($user->isSuperAdmin() || $user->isAdmin());
    }

    protected function getActions(): array
    {
        // Only superadmin can send summary to Discord
        if (!auth()->user()?->isSuperAdmin()) {
            return [];
        }

        return [
            Action::make('sendSummary')
                ->label('Kirim Summary ke Discord')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->action(function () {
                    app(AdminProgressService::class)->sendProgressSummary();
                    
                    Notification::make()
                        ->title('Summary Terkirim!')
                        ->success()
                        ->body('Admin progress summary telah dikirim ke Discord')
                        ->send();
                }),
        ];
    }

    protected function getViewData(): array
    {
        $progressService = app(AdminProgressService::class);
        $currentUser = auth()->user();
        $isSuperAdmin = $currentUser->isSuperAdmin();
        
        // Superadmin sees all admins, regular admin sees only themselves
        if ($isSuperAdmin) {
            $admins = User::admins()
                ->where('role', '!=', User::ROLE_SUPERADMIN)
                ->get();
        } else {
            $admins = collect([$currentUser]);
        }

        $adminProgress = [];
        foreach ($admins as $admin) {
            $adminProgress[] = $progressService->getAdminProgress($admin);
        }

        // Sort by performance score desc
        usort($adminProgress, fn($a, $b) => $b['performance_score'] <=> $a['performance_score']);

        return [
            'adminProgress' => $adminProgress,
            'month' => now()->format('F Y'),
            'isSuperAdmin' => $isSuperAdmin,
        ];
    }
}
