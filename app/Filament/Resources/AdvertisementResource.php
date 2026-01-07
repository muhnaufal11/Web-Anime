<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdvertisementResource\Pages;
use App\Models\Advertisement;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class AdvertisementResource extends Resource
{
    protected static ?string $model = Advertisement::class;

    protected static ?string $navigationIcon = 'heroicon-o-speakerphone';
    
    protected static ?string $navigationLabel = 'Iklan';
    
    protected static ?string $navigationGroup = 'Pengaturan';
    
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Iklan')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: AdSense Header Banner'),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('position')
                                    ->label('Posisi')
                                    ->options(Advertisement::POSITIONS)
                                    ->required(),
                                    
                                Forms\Components\Select::make('type')
                                    ->label('Tipe Iklan')
                                    ->options(Advertisement::TYPES)
                                    ->required()
                                    ->reactive(),
                            ]),
                            
                        Forms\Components\Select::make('size')
                            ->label('Ukuran')
                            ->options(Advertisement::SIZES)
                            ->default('responsive'),
                    ])
                    ->columnSpan(2),
                    
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Textarea::make('code')
                            ->label('Kode Iklan (AdSense / HTML / JavaScript)')
                            ->rows(8)
                            ->placeholder('Paste kode AdSense atau HTML custom di sini...')
                            ->helperText('Untuk Google AdSense, paste kode yang didapat dari dashboard AdSense')
                            ->visible(fn (callable $get) => in_array($get('type'), ['adsense', 'custom', 'html', null])),
                            
                        Forms\Components\FileUpload::make('image_path')
                            ->label('Gambar Iklan')
                            ->image()
                            ->directory('ads')
                            ->visibility('public')
                            ->visible(fn (callable $get) => $get('type') === 'image'),
                            
                        Forms\Components\TextInput::make('link')
                            ->label('Link Target')
                            ->url()
                            ->placeholder('https://example.com')
                            ->visible(fn (callable $get) => $get('type') === 'image'),
                    ])
                    ->columnSpan(2),
                    
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('order')
                                    ->label('Urutan')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Semakin kecil, semakin atas'),
                                    
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Aktif')
                                    ->default(true),
                            ]),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('start_date')
                                    ->label('Tanggal Mulai'),
                                    
                                Forms\Components\DateTimePicker::make('end_date')
                                    ->label('Tanggal Berakhir'),
                            ]),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('show_on_mobile')
                                    ->label('Tampil di Mobile')
                                    ->default(true),
                                    
                                Forms\Components\Toggle::make('show_on_desktop')
                                    ->label('Tampil di Desktop')
                                    ->default(true),
                            ]),
                    ])
                    ->columnSpan(1),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('position')
                    ->label('Posisi')
                    ->formatStateUsing(fn ($state) => Advertisement::POSITIONS[$state] ?? $state)
                    ->colors([
                        'primary' => fn ($state) => in_array($state, ['header_top', 'header_bottom']),
                        'success' => fn ($state) => in_array($state, ['sidebar_top', 'sidebar_bottom']),
                        'warning' => fn ($state) => in_array($state, ['content_top', 'content_middle', 'content_bottom']),
                        'danger' => fn ($state) => in_array($state, ['footer', 'popup', 'floating']),
                    ]),
                    
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipe')
                    ->formatStateUsing(fn ($state) => Advertisement::TYPES[$state] ?? $state)
                    ->colors([
                        'primary' => 'adsense',
                        'success' => 'image',
                        'warning' => 'custom',
                        'secondary' => 'html',
                    ]),
                    
                Tables\Columns\TextColumn::make('impressions')
                    ->label('Impressions')
                    ->formatStateUsing(fn ($state) => number_format($state))
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('clicks')
                    ->label('Clicks')
                    ->formatStateUsing(fn ($state) => number_format($state))
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('ctr')
                    ->label('CTR')
                    ->formatStateUsing(fn ($record) => $record->ctr . '%')
                    ->color(fn ($record) => $record->ctr >= 1 ? 'success' : ($record->ctr >= 0.5 ? 'warning' : 'danger')),
                    
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif'),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('position')
                    ->label('Posisi')
                    ->options(Advertisement::POSITIONS),
                    
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe')
                    ->options(Advertisement::TYPES),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdvertisements::route('/'),
            'create' => Pages\CreateAdvertisement::route('/create'),
            'edit' => Pages\EditAdvertisement::route('/{record}/edit'),
        ];
    }
}
