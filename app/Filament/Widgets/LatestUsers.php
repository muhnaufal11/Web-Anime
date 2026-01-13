<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestUsers extends BaseWidget
{
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 1;
    
    protected static ?string $heading = '👤 User Terbaru';

    protected function getTableQuery(): Builder
    {
        return User::query()
            ->latest()
            ->limit(5);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label('Nama')
                ->searchable(),
            Tables\Columns\BadgeColumn::make('role')
                ->label('Role')
                ->colors([
                    'gray' => User::ROLE_USER,
                    'success' => User::ROLE_ADMIN,
                    'warning' => User::ROLE_SUPERADMIN,
                ]),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Bergabung')
                ->since(),
        ];
    }
}
