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

    // Properti untuk menyimpan pilihan filter
    public $year = '';
    public $minRating = '';
    public $sortBy = 'rating'; // Default sort by rating
    public $showDropped = false; // Hide dropped by default

    protected int $perPage = 50;
    protected $paginationTheme = 'tailwind';
    protected $queryString = ['year', 'minRating', 'sortBy', 'showDropped'];

    public function updatedYear(): void
    {
        $this->resetPage();
    }

    public function updatedMinRating(): void
    {
        $this->resetPage();
    }

    public function updatedSortBy(): void
    {
        $this->resetPage();
    }

    public function updatedShowDropped(): void
    {
        $this->resetPage();
    }

    /**
     * Set anime status to Dropped
     */
    public function setDropped(int $animeId): void
    {
        $anime = Anime::find($animeId);
        if ($anime) {
            $anime->update(['status' => 'Dropped']);
            \Filament\Notifications\Notification::make()
                ->title('Status Updated')
                ->success()
                ->body("'{$anime->title}' telah di-set sebagai Dropped")
                ->send();
        }
    }

    /**
     * Unset anime from Dropped status
     */
    public function unsetDropped(int $animeId): void
    {
        $anime = Anime::find($animeId);
        if ($anime) {
            $anime->update(['status' => 'Ongoing']);
            \Filament\Notifications\Notification::make()
                ->title('Status Updated')
                ->success()
                ->body("'{$anime->title}' telah diaktifkan kembali")
                ->send();
        }
    }

    protected function baseQuery(): Builder
    {
        return Anime::query()
            ->select(['animes.id', 'animes.title', 'animes.release_year', 'animes.poster_image', 'animes.rating', 'animes.status'])
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
            ->when($this->year, fn ($query) => $query->where('release_year', $this->year))
            ->when($this->minRating, fn ($query) => $query->where('rating', '>=', (float) $this->minRating))
            ->when(!$this->showDropped, fn ($query) => $query->where(function ($q) {
                $q->where('status', '!=', 'Dropped')->orWhereNull('status');
            }));
    }

    /**
     * Anime yang belum punya video server (dengan info sync/manual)
     */
    protected function getAnimes(): LengthAwarePaginator
    {
        $query = $this->baseQuery();
        
        // Apply sorting
        switch ($this->sortBy) {
            case 'rating':
                $query->orderByDesc('rating');
                break;
            case 'year':
                $query->orderByDesc('release_year');
                break;
            case 'episodes':
                $query->orderByDesc('episodes_no_video');
                break;
            case 'title':
                $query->orderBy('title');
                break;
            default:
                $query->orderByDesc('rating');
        }
        
        return $query
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

    // Rating presets untuk filter cepat
    protected function getRatingPresets(): array
    {
        return [
            '' => 'Semua Rating',
            '8' => '⭐ 8+ (Excellent)',
            '7' => '⭐ 7+ (Great)',
            '6' => '⭐ 6+ (Good)',
            '5' => '⭐ 5+ (Average)',
        ];
    }

    // Sort options
    protected function getSortOptions(): array
    {
        return [
            'rating' => '⭐ Rating (Tertinggi)',
            'year' => '📅 Tahun (Terbaru)',
            'episodes' => '🎬 Episode Tanpa Video',
            'title' => '🔤 Judul (A-Z)',
        ];
    }

    protected function getViewData(): array
    {
        return [
            'animes' => $this->getAnimes(),
            'years' => $this->getAvailableYears(),
            'totalAnimes' => $this->getTotalAnimes(),
            'ratingPresets' => $this->getRatingPresets(),
            'sortOptions' => $this->getSortOptions(),
        ];
    }
}