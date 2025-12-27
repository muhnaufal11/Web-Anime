<?php

namespace App\Filament\Resources\AnimeResource\Pages;

use App\Filament\Resources\AnimeResource;
use App\Models\Anime;
use Filament\Pages\Page;

class ListEmptyAnimes extends Page
{
    protected static string $resource = AnimeResource::class;
    protected static string $view = 'filament.resources.anime-resource.pages.list-empty-animes';

    public $animes;

    public function mount()
    {
        // Query: anime tanpa episode atau episode tanpa video server
        $this->animes = Anime::whereDoesntHave('episodes')
            ->orWhereHas('episodes', function($q) {
                $q->doesntHave('videoServers');
            })
            ->get();
    }
}
