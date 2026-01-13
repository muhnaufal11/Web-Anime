<?php

namespace App\Filament\Widgets;

use App\Models\Anime;
use Filament\Widgets\DoughnutChartWidget;

class AnimeByStatusChart extends DoughnutChartWidget
{
    protected static ?int $sort = 5;
    
    protected static ?string $heading = '🎯 Status Anime';
    
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $statuses = Anime::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
            
        return [
            'datasets' => [
                [
                    'label' => 'Jumlah',
                    'data' => array_values($statuses),
                    'backgroundColor' => [
                        'rgba(34, 197, 94, 0.8)',   // green for Completed
                        'rgba(234, 179, 8, 0.8)',   // yellow for Ongoing
                        'rgba(156, 163, 175, 0.8)', // gray for others
                    ],
                ],
            ],
            'labels' => array_keys($statuses),
        ];
    }
}
