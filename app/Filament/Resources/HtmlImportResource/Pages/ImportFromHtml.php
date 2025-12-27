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
use Livewire\WithFileUploads;

class ImportFromHtml extends Page implements Forms\Contracts\HasForms
{
    // Download all episode HTMLs as ZIP and return response
    public function downloadAllEpisodeHtmls()
    {
        if (!$this->parsedData || empty($this->parsedData['episodes'])) {
            Notification::make()
                ->title('Tidak ada episode')
                ->body('Parse HTML terlebih dahulu')
                ->danger();
            // ...existing code...

                class ImportFromHtml extends Page
                {
                    use WithFileUploads;

                    public $htmlFile;

                    protected static string $view = 'filament.resources.html-import-resource.pages.import-from-html';

                    public function downloadAllEpisodeHtmls()
                    {
                        if (!$this->htmlFile) {
                            Notification::make()
                                ->title('Tidak ada file HTML')
                                ->danger()
                                ->send();
                            return;
                        }

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
                            Notification::make()
                                ->title('Gagal membuat ZIP')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Download ZIP ke browser
                        return response()->download($zipPath)->deleteFileAfterSend(true);
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
                    Notification::make()
                        ->title('Gagal membuat ZIP')
                        ->danger()
                        ->send();
                    return;
                }

                // Download ZIP ke browser
                return response()->download($zipPath)->deleteFileAfterSend(true);
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
                                Notification::make()
                                    ->title('Gagal membuat ZIP')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // Download ZIP ke browser
                            return response()->download($zipPath)->deleteFileAfterSend(true);
                        }
                    }
            $file = is_array($this->htmlFile) ? $this->htmlFile[0] : $this->htmlFile;
            $content = $file->get();
            $this->parseAndPreview($content);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error membaca file')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function parseHtmlContent()
    {
        if (empty($this->htmlContent)) {
            return;
        }

        $this->parseAndPreview($this->htmlContent);
    }

    protected function parseAndPreview(string $html)
    {
        $scraper = new AnimeScraper();
        $this->parsedData = $scraper->parseAnimeFromHtml($html);
        
        Notification::make()
            ->title('HTML berhasil di-parse!')
            ->body("Ditemukan: {$this->parsedData['title']} dengan " . count($this->parsedData['episodes']) . " episode")
            ->success()
            ->send();
    }

    public function startImport()
    {
        if (!$this->parsedData) {
            Notification::make()
                ->title('Tidak ada data')
                ->body('Parse HTML terlebih dahulu')
                ->danger()
                ->send();
            return;
        }

        $this->isImporting = true;
        $this->importProgress = [];
        
        try {
            // 1. Create or find genres
            $this->addProgress('Memproses genre...');
            $genreIds = [];
            foreach ($this->parsedData['genres'] as $genreName) {
                $genre = Genre::firstOrCreate(
                    ['name' => $genreName],
                    ['slug' => Str::slug($genreName)]
                );
                $genreIds[] = $genre->id;
            }
            $this->addProgress('✓ ' . count($genreIds) . ' genre diproses');

            // 2. Create anime
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
            $this->addProgress('✓ Anime dibuat: ' . $anime->title);

            // 3. Download poster
            if (!empty($this->parsedData['poster_url'])) {
                $this->addProgress('Mendownload poster...');
                $scraper = new AnimeScraper();
                $posterPath = $scraper->downloadPoster(
                    $this->parsedData['poster_url'],
                    $anime->slug . '-' . time()
                );
                if ($posterPath) {
                    $anime->update(['poster_image' => $posterPath]);
                    $this->addProgress('✓ Poster disimpan');
                }
            }

            // 4. Process episodes
            $this->addProgress('Memproses ' . count($this->parsedData['episodes']) . ' episode...');
            $scraper = new AnimeScraper();
            
            foreach ($this->parsedData['episodes'] as $index => $epData) {
                $this->addProgress("Episode {$epData['number']}: Membuat...");
                
                // Create episode
                $episode = Episode::create([
                    'anime_id' => $anime->id,
                    'episode_number' => $epData['number'],
                    'title' => "Episode {$epData['number']}",
                ]);
                
                // Scrape video servers if enabled
                if ($this->scrapeEpisodes && !empty($epData['url'])) {
                    $this->addProgress("Episode {$epData['number']}: Scraping servers...");
                    
                    $servers = $scraper->scrapeEpisodePage($epData['url']);
                    
                    foreach ($servers as $serverData) {
                        VideoServer::create([
                            'episode_id' => $episode->id,
                            'name' => $serverData['name'],
                            'url' => $serverData['url'],
                        ]);
                    }
                    
                    $this->addProgress("✓ Episode {$epData['number']}: " . count($servers) . " servers");
                    
                    // Delay to avoid rate limiting
                    if ($index < count($this->parsedData['episodes']) - 1) {
                        sleep($this->delayBetweenRequests);
                    }
                } else {
                    $this->addProgress("✓ Episode {$epData['number']}: Dibuat (tanpa scrape)");
                }
            }

            $this->addProgress('');
            $this->addProgress('========================================');
            $this->addProgress('✅ IMPORT SELESAI!');
            $this->addProgress("Anime: {$anime->title}");
            $this->addProgress("Total Episode: " . $anime->episodes()->count());
            $this->addProgress('========================================');

            Notification::make()
                ->title('Import Berhasil!')
                ->body("Anime '{$anime->title}' berhasil diimport dengan " . $anime->episodes()->count() . " episode")
                ->success()
                ->send();

        } catch (\Exception $e) {
            $this->addProgress('❌ ERROR: ' . $e->getMessage());
            
            Notification::make()
                ->title('Error saat import')
                ->body($e->getMessage())
                ->danger()
                ->send();
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
