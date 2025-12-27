<?php

namespace App\Filament\Resources\HtmlImportResource\Pages;

use App\Filament\Resources\HtmlImportResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Pages\Actions;

class ListHtmlImports extends ListRecords
{
    protected static string $resource = HtmlImportResource::class;
    
    protected static ?string $title = 'Import dari HTML';

    protected function getActions(): array
    {
        return [
            Actions\Action::make('import')
                ->label('Import Anime dari HTML')
                ->icon('heroicon-o-upload')
                ->url(HtmlImportResource::getUrl('import'))
                ->color('success'),
        ];
    }
}
