<?php

namespace App\Filament\Resources\AnimeResource\Pages;

use App\Filament\Resources\AnimeResource;
use App\Models\Anime;
use Filament\Resources\Pages\Page;

class ListEmptyAnimes extends Page
{
    protected static string $resource = AnimeResource::class;
    protected static string $view = 'filament.resources.anime-resource.pages.list-empty-animes';

    // Properti untuk menyimpan pilihan filter tahun
    public $year = '';

    /**
     * Anime yang belum punya video server (dengan info sync/manual)
     */
    protected function getAnimes()
    {
        return Anime::withCount([
            'episodes', 
            // Episode tanpa video server sama sekali
            'episodes as episodes_no_video' => function ($query) {
                $query->doesntHave('videoServers');
            },
            // Episode tanpa server sync
            'episodes as episodes_no_sync' => function ($query) {
                $query->whereDoesntHave('videoServers', function($q) {
                    $q->where('source', 'sync');
                });
            },
            // Episode tanpa server manual
            'episodes as episodes_no_manual' => function ($query) {
                $query->whereDoesntHave('videoServers', function($q) {
                    $q->where('source', 'manual');
                });
            }
        ])
        ->where(function($query) {
            // Belum ada episode sama sekali
            $query->whereDoesntHave('episodes')
                  // ATAU ada episode yang belum punya video server apapun
                  ->orWhereHas('episodes', function($q) {
                      $q->doesntHave('videoServers');
                  })
                  // ATAU ada episode yang belum punya server manual (admin upload)
                  ->orWhereHas('episodes', function($q) {
                      $q->whereDoesntHave('videoServers', function($vs) {
                          $vs->where('source', 'manual');
                      });
                  })
                  // ATAU ada episode yang belum punya server sync
                  ->orWhereHas('episodes', function($q) {
                      $q->whereDoesntHave('videoServers', function($vs) {
                          $vs->where('source', 'sync');
                      });
                  });
        })
        ->when($this->year, fn ($query) => $query->where('release_year', $this->year))
        // Urutan: yang punya episode tapi belum ada server dulu, baru yang belum ada episode
        ->orderByRaw('CASE WHEN (SELECT COUNT(*) FROM episodes WHERE episodes.anime_id = animes.id) = 0 THEN 1 ELSE 0 END')
        ->orderByDesc('episodes_no_video')
        ->orderBy('title')
        ->get();
    }

    // Mengambil daftar tahun unik untuk dropdown filter
    protected function getAvailableYears()
    {
        return Anime::whereNotNull('release_year')
            ->distinct()
            ->orderBy('release_year', 'desc')
            ->pluck('release_year');
    }

    protected function getViewData(): array
    {
        return [
            'animes' => $this->getAnimes(),
            'years' => $this->getAvailableYears(),
        ];
    }
}