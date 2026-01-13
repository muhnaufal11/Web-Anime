<?php

namespace App\Filament\Resources\HtmlImportResource\Pages;

use App\Filament\Resources\HtmlImportResource;
use App\Models\Anime;
use App\Models\Genre;
use App\Models\Episode;
use App\Models\VideoServer;
use App\Services\AnimeScraper;
use Filament\Forms;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http; // Tambahkan ini untuk fungsi Http
use Livewire\WithFileUploads;

class ImportFromHtml extends Page implements Forms\Contracts\HasForms
{
    use WithFileUploads;

    public $htmlFile;
    public $htmlContent = '';
    public $parsedData = null;
    public $isImporting = false;
    public $importProgress = [];
    public $scrapeEpisodes = true;
    public $delayBetweenRequests = 1;

    protected static string $resource = HtmlImportResource::class;
    protected static string $view = 'filament.resources.html-import-resource.pages.import-from-html';

    // FUNGSI UNTUK DOWNLOAD ZIP
    public function downloadAllEpisodeHtmls()
    {
        if (!$this->htmlFile) {
            Notification::make()
                ->title('Tidak ada file HTML')
                ->danger()
                ->send();
            return;
        }

        try {
            // Ambil isi file HTML utama
            $content = $this->htmlFile->get();

            // Ambil semua link episode dari <ul class="daftar">
            preg_match('/<ul class="daftar">(.*?)<\/ul>/is', $content, $listMatch);
            $episodeLinks = [];
            if (!empty($listMatch[1])) {
                preg_match_all('/<li>\s*<a href="([^"]+)"[^>]*>([^<]+)<\/a>/i', $listMatch[1], $matches, PREG_SET_ORDER);
                foreach ($matches as $ep) {
                    $episodeLinks[] = $ep[1];
                }
            }

            if (empty($episodeLinks)) {
                Notification::make()
                    ->title('Tidak ada link episode ditemukan')
                    ->danger()
                    ->send();
                return;
            }

            // Download semua HTML episode
            $episodeHtmls = [];
            foreach ($episodeLinks as $url) {
                try {
                    $html = Http::timeout(30)
                        ->withHeaders([
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                            'Accept' => 'text/html,application/xhtml+xml',
                        ])->get($url)->body();
                    $episodeHtmls[] = [
                        'url' => $url,
                        'html' => $html,
                    ];
                } catch (\Exception $e) {
                    $episodeHtmls[] = [
                        'url' => $url,
                        'html' => '',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            // Buat ZIP file di storage temporary
            $zipPath = storage_path('app/public/episodes_html_' . time() . '.zip');
            $zip = new \ZipArchive();
            
            if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
                foreach ($episodeHtmls as $i => $ep) {
                    $filename = 'episode-' . ($i + 1) . '.html';
                    $zip->addFromString($filename, $ep['html'] ?? '');
                }
                $zip->close();
            } else {
                throw new \Exception("Gagal membuat file ZIP di server.");
            }

            // Download ZIP ke browser
            return response()->download($zipPath)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error!')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function parseHtmlContent()
    {
        if (empty($this->htmlContent)) return;
        $this->parseAndPreview($this->htmlContent);
    }

    protected function parseAndPreview(string $html)
    {
        $scraper = new AnimeScraper();
        $this->parsedData = $scraper->parseAnimeFromHtml($html);
        
        Notification::make()
            ->title('HTML berhasil di-parse!')
            ->body("Ditemukan: {$this->parsedData['title']}")
            ->success()
            ->send();
    }

    public function startImport()
    {
        if (!$this->parsedData) {
            Notification::make()->title('Data kosong')->danger()->send();
            return;
        }

        $this->isImporting = true;
        $this->importProgress = [];
        
        try {
            $this->addProgress('Memproses genre...');
            $genreIds = [];
            foreach ($this->parsedData['genres'] as $genreName) {
                $genre = Genre::firstOrCreate(['name' => $genreName], ['slug' => Str::slug($genreName)]);
                $genreIds[] = $genre->id;
            }

            $this->addProgress('Membuat anime...');
            $anime = Anime::create([
                'title' => $this->parsedData['title'],
                'slug' => Str::slug($this->parsedData['title']),
                'synopsis' => $this->parsedData['synopsis'],
                'type' => $this->parsedData['type'],
                'status' => $this->parsedData['status'],
                'rating' => $this->parsedData['rating'],
                'release_year' => $this->parsedData['release_year'],
            ]);
            $anime->genres()->sync($genreIds);

            // Download poster
            if (!empty($this->parsedData['poster_url'])) {
                $scraper = new AnimeScraper();
                $posterPath = $scraper->downloadPoster($this->parsedData['poster_url'], $anime->slug . '-' . time());
                if ($posterPath) $anime->update(['poster_image' => $posterPath]);
            }

            // Process episodes
            foreach ($this->parsedData['episodes'] as $index => $epData) {
                $episode = Episode::create([
                    'anime_id' => $anime->id,
                    'episode_number' => $epData['number'],
                    'title' => "Episode {$epData['number']}",
                ]);
                
                if ($this->scrapeEpisodes && !empty($epData['url'])) {
                    $scraper = new AnimeScraper();
                    $servers = $scraper->scrapeEpisodePage($epData['url']);
                    foreach ($servers as $serverData) {
                        VideoServer::create([
                            'episode_id' => $episode->id,
                            'name' => $serverData['name'],
                            'url' => $serverData['url'],
                        ]);
                    }
                }
            }

            Notification::make()->title('Import Berhasil!')->success()->send();

        } catch (\Exception $e) {
            $this->addProgress('❌ ERROR: ' . $e->getMessage());
        }

        $this->isImporting = false;
    }

    protected function addProgress(string $message)
    {
        $this->importProgress[] = '[' . now()->format('H:i:s') . '] ' . $message;
    }

    public function clearData()
    {
        $this->parsedData = null;
        $this->htmlFile = null;
        $this->htmlContent = '';
        $this->importProgress = [];
    }
}