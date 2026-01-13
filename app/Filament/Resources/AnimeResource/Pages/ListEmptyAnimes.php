<?php

namespace App\Filament\Resources\AnimeResource\Pages;

use App\Filament\Resources\AnimeResource;
use App\Models\Anime;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\WithPagination;

class ListEmptyAnimes extends Page
{
    use WithPagination;

    protected static string $resource = AnimeResource::class;
    protected static string $view = 'filament.resources.anime-resource.pages.list-empty-animes';

    // Properti untuk menyimpan pilihan filter tahun
    public $year = '';

    protected int $perPage = 50;
    protected $paginationTheme = 'tailwind';
    protected $queryString = ['year'];

    public function updatedYear(): void
    {
        $this->resetPage();
    }

    protected function baseQuery(): Builder
    {
        return Anime::query()
            ->select(['animes.id', 'animes.title', 'animes.release_year', 'animes.poster_image'])
            ->withCount([
                'episodes',
                'episodes as episodes_no_video' => function ($query) {
                    $query->doesntHave('videoServers');
                },
                'episodes as episodes_no_sync' => function ($query) {
                    $query->whereDoesntHave('videoServers', function ($q) {
                        $q->where('source', 'sync');
                    });
                },
                'episodes as episodes_no_manual' => function ($query) {
                    $query->whereDoesntHave('videoServers', function ($q) {
                        $q->where('source', 'manual');
                    });
                },
            ])
            ->where(function ($query) {
                $query->whereDoesntHave('episodes')
                    ->orWhereHas('episodes', function ($q) {
                        $q->doesntHave('videoServers');
                    })
                    ->orWhereHas('episodes', function ($q) {
                        $q->whereDoesntHave('videoServers', function ($vs) {
                            $vs->where('source', 'manual');
                        });
                    })
                    ->orWhereHas('episodes', function ($q) {
                        $q->whereDoesntHave('videoServers', function ($vs) {
                            $vs->where('source', 'sync');
                        });
                    });
            })
            ->when($this->year, fn ($query) => $query->where('release_year', $this->year));
    }

    /**
     * Anime yang belum punya video server (dengan info sync/manual)
     */
    protected function getAnimes(): LengthAwarePaginator
    {
        return $this->baseQuery()
            ->orderByRaw('CASE WHEN episodes_count = 0 THEN 1 ELSE 0 END')
            ->orderByDesc('episodes_no_video')
            ->orderBy('title')
            ->paginate($this->perPage);
    }

    protected function getTotalAnimes(): int
    {
        return $this->baseQuery()->count();
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
            'totalAnimes' => $this->getTotalAnimes(),
        ];
    }
}