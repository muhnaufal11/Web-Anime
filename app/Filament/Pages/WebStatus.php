<?php

namespace App\Filament\Pages;

use App\Services\WebStatusService;
use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Actions\Action;

class WebStatus extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationLabel = 'Web Status';
    protected static ?string $navigationGroup = 'SUPERADMIN';
    protected static ?int $navigationSort = 11;
    protected static string $view = 'filament.pages.web-status';

    public ?string $status = '';
    public ?string $message = '';
    public ?string $estimatedTime = '';
    public ?string $announcementTitle = '';
    public ?string $announcementMessage = '';
    public ?string $announcementType = 'info';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public function mount(): void
    {
        $service = app(WebStatusService::class);
        $current = $service->getCurrentStatus();
        
        $this->status = $current['status'];
        $this->message = $current['message'];
    }

    public function form(Form $form): Form
    {
        $service = app(WebStatusService::class);
        $statuses = $service->getStatuses();
        
        $options = [];
        foreach ($statuses as $key => $config) {
            $options[$key] = $config['emoji'] . ' ' . $config['label'];
        }

        return $form->schema([
            Section::make('Update Status Website')
                ->description('Ubah status website dan kirim notifikasi ke Discord')
                ->schema([
                    Select::make('status')
                        ->label('Status')
                        ->options($options)
                        ->required(),
                    Textarea::make('message')
                        ->label('Keterangan')
                        ->placeholder('Contoh: Sedang update fitur baru...')
                        ->rows(3),
                    TextInput::make('estimatedTime')
                        ->label('Estimasi Selesai (opsional)')
                        ->placeholder('Contoh: 30 menit, 2 jam, dll'),
                ]),
        ]);
    }

    public function updateStatus(): void
    {
        $service = app(WebStatusService::class);
        
        $success = $service->setStatus(
            $this->status,
            $this->message ?? '',
            $this->estimatedTime ?: null
        );

        if ($success) {
            Notification::make()
                ->title('Status Terupdate!')
                ->success()
                ->body('Status website telah diubah dan notifikasi terkirim ke Discord')
                ->send();
        } else {
            Notification::make()
                ->title('Gagal!')
                ->danger()
                ->body('Status tidak valid')
                ->send();
        }
    }

    public function sendAnnouncement(): void
    {
        if (empty($this->announcementTitle) || empty($this->announcementMessage)) {
            Notification::make()
                ->title('Error')
                ->danger()
                ->body('Judul dan pesan harus diisi')
                ->send();
            return;
        }

        $service = app(WebStatusService::class);
        $service->sendCustomAnnouncement(
            $this->announcementTitle,
            $this->announcementMessage,
            $this->announcementType ?? 'info'
        );

        Notification::make()
            ->title('Pengumuman Terkirim!')
            ->success()
            ->body('Pengumuman telah dikirim ke Discord')
            ->send();

        // Reset form
        $this->announcementTitle = '';
        $this->announcementMessage = '';
        $this->announcementType = 'info';
    }

    protected function getViewData(): array
    {
        $service = app(WebStatusService::class);
        
        // Health check data
        $healthStatus = \Illuminate\Support\Facades\Cache::get('health_check_status', 'unknown');
        $healthLastRun = \Illuminate\Support\Facades\Cache::get('health_check_last_run', 'Never');
        $healthResults = \Illuminate\Support\Facades\Cache::get('health_check_results', []);
        $healthFailures = \Illuminate\Support\Facades\Cache::get('health_check_failures', 0);
        
        return [
            'currentStatus' => $service->getCurrentStatus(),
            'statuses' => $service->getStatuses(),
            'bypassUrl' => $service->getBypassUrl(),
            'healthStatus' => $healthStatus,
            'healthLastRun' => $healthLastRun,
            'healthResults' => $healthResults,
            'healthFailures' => $healthFailures,
        ];
    }

    public function runHealthCheck(): void
    {
        \Illuminate\Support\Facades\Artisan::call('health:check');
        
        Notification::make()
            ->title('Health Check Selesai')
            ->success()
            ->body('Health check telah dijalankan')
            ->send();
    }
}
