<?php

namespace App\Filament\Resources\AnimeResource\Pages;

use App\Filament\Resources\AnimeResource;
use App\Models\Anime;
use Filament\Resources\Pages\Page;

class ListEmptyAnimes extends Page
{
    protected static string $resource = AnimeResource::class;
    protected static string $view = 'filament.resources.anime-resource.pages.list-empty-animes';

    public $animes;

    public function mount()
    {
        // Query yang lebih detail untuk menghitung kondisi episode
        $this->animes = Anime::withCount([
            'episodes', 
            // Menghitung jumlah episode yang TIDAK punya video server
            'episodes as missing_video_count' => function ($query) {
                $query->doesntHave('videoServers');
            }
        ])
        ->whereDoesntHave('episodes')
        ->orWhereHas('episodes', function($q) {
            $q->doesntHave('videoServers');
        })
        ->get();
    }
}