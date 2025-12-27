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
    use Forms\Concerns\InteractsWithForms;
    use WithFileUploads;

    protected static string $resource = HtmlImportResource::class;
    
    protected static string $view = 'filament.resources.html-import-resource.pages.import-from-html';
    
    protected static ?string $title = 'Import Anime dari HTML';

    public $htmlFile;
    public $htmlContent = '';
    public $parsedData = null;
    public $importProgress = [];
    public $isImporting = false;
    public $scrapeEpisodes = true;
    public $delayBetweenRequests = 2;

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Upload File HTML')
                ->description('Upload file HTML halaman detail anime (contoh: dari AnimeSail)')
                ->schema([
                    Forms\Components\FileUpload::make('htmlFile')
                        ->label('File HTML')
                        ->acceptedFileTypes(['text/html', '.html', '.htm'])
                        ->directory('temp-imports')
                        ->reactive()
                        ->afterStateUpdated(fn () => $this->parseHtmlFile()),
                    
                    Forms\Components\Textarea::make('htmlContent')
                        ->label('Atau Paste HTML Content')
                        ->rows(5)
                        ->placeholder('Paste HTML content di sini...')
                        ->reactive()
                        ->afterStateUpdated(fn () => $this->parseHtmlContent()),
                ]),
            
            Forms\Components\Section::make('Opsi Import')
                ->schema([
                    Forms\Components\Toggle::make('scrapeEpisodes')
                        ->label('Scrape halaman episode untuk video servers')
                        ->helperText('Akan mengunjungi setiap link episode untuk mengambil video servers')
                        ->default(true),
                    
                    Forms\Components\TextInput::make('delayBetweenRequests')
                        ->label('Delay antar request (detik)')
                        ->numeric()
                        ->default(2)
                        ->minValue(1)
                        ->maxValue(10)
                        ->helperText('Untuk menghindari rate limiting'),
                ]),
        ];
    }

    public function parseHtmlFile()
    {
        if (!$this->htmlFile) {
            return;
        }

        try {
            $path = storage_path('app/public/' . $this->htmlFile);
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $this->parseAndPreview($content);
            }
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
