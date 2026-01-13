<?php

namespace App\Filament\Widgets;

use App\Models\Anime;
use Filament\Widgets\BarChartWidget;

class AnimeByTypeChart extends BarChartWidget
{
    protected static ?int $sort = 4;
    
    protected static ?string $heading = '📊 Anime per Tipe';
    
    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $types = Anime::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
            
        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Anime',
                    'data' => array_values($types),
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.8)',  // blue
                        'rgba(234, 179, 8, 0.8)',   // yellow
                        'rgba(34, 197, 94, 0.8)',   // green
                        'rgba(168, 85, 247, 0.8)', // purple
                    ],
                ],
            ],
            'labels' => array_keys($types),
        ];
    }
}
