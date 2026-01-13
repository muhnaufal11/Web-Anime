<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Pages\Page;
use Filament\Forms;

class HolidaySettings extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles'; // Gunakan icon bintang/percikan
    protected static ?string $navigationGroup = 'TOOLS'; // Masuk ke grup Tools
    protected static string $view = 'filament.pages.holiday-settings';

    public $christmas_mode;
    public $new_year_mode;

    public function mount()
    {
        $this->form->fill([
            'christmas_mode' => SiteSetting::where('key', 'christmas_mode')->first()?->value == '1',
            'new_year_mode' => SiteSetting::where('key', 'new_year_mode')->first()?->value == '1',
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Card::make()->schema([
                Forms\Components\Toggle::make('christmas_mode')
                    ->label('Aktifkan Tema Natal (Efek Salju)')
                    ->helperText('Jika aktif, seluruh halaman user akan muncul salju.'),
                Forms\Components\Toggle::make('new_year_mode')
                    ->label('Aktifkan Tema Tahun Baru (Kembang Api)')
                    ->helperText('Jika aktif, akan muncul efek kembang api.'),
            ])
        ];
    }

    public function save()
    {
        $data = $this->form->getState();
        SiteSetting::updateOrCreate(['key' => 'christmas_mode'], ['value' => $data['christmas_mode'] ? '1' : '0']);
        SiteSetting::updateOrCreate(['key' => 'new_year_mode'], ['value' => $data['new_year_mode'] ? '1' : '0']);

        $this->notify('success', 'Tema berhasil diperbarui!');
    }
}