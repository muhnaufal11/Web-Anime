<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduleResource\Pages;
use App\Filament\Resources\ScheduleResource\RelationManagers;
use App\Models\Schedule;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Jadwal Tayang';

    protected static ?string $pluralLabel = 'Jadwal Tayang';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('anime_id')
                    ->label('Anime')
                    ->relationship('anime', 'title')
                    ->searchable()
                    ->required()
                    ->preload()
                    ->columnSpan('full'),
                
                Forms\Components\Select::make('day_of_week')
                    ->label('Hari Tayang')
                    ->options([
                        'Monday' => 'Senin',
                        'Tuesday' => 'Selasa',
                        'Wednesday' => 'Rabu',
                        'Thursday' => 'Kamis',
                        'Friday' => 'Jumat',
                        'Saturday' => 'Sabtu',
                        'Sunday' => 'Minggu',
                    ])
                    ->required()
                    ->columnSpan(1),
                
                Forms\Components\TimePicker::make('broadcast_time')
                    ->label('Jam Tayang')
                    ->hoursStep(1)
                    ->minutesStep(1)
                    ->format('H:i')
                    ->columnSpan(1),
                
                Forms\Components\DatePicker::make('next_episode_date')
                    ->label('Tanggal Episode Berikutnya')
                    ->displayFormat('d/m/Y')
                    ->columnSpan(1),
                
                Forms\Components\Select::make('timezone')
                    ->label('Timezone')
                    ->options([
                        'Asia/Jakarta' => 'WIB (Jakarta)',
                        'Asia/Makassar' => 'WITA (Makassar)',
                        'Asia/Jayapura' => 'WIT (Jayapura)',
                        'Asia/Tokyo' => 'JST (Tokyo)',
                    ])
                    ->default('Asia/Jakarta')
                    ->required()
                    ->columnSpan(1),
                
                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->columnSpan(1),
                
                Forms\Components\Textarea::make('notes')
                    ->label('Catatan')
                    ->rows(3)
                    ->columnSpan('full'),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('anime.title')
                    ->label('Anime')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                
                Tables\Columns\BadgeColumn::make('day_of_week')
                    ->label('Hari')
                    ->formatStateUsing(fn (string $state): string => [
                        'Monday' => 'Senin',
                        'Tuesday' => 'Selasa',
                        'Wednesday' => 'Rabu',
                        'Thursday' => 'Kamis',
                        'Friday' => 'Jumat',
                        'Saturday' => 'Sabtu',
                        'Sunday' => 'Minggu',
                    ][$state] ?? $state)
                    ->colors([
                        'primary' => 'Monday',
                        'success' => 'Tuesday',
                        'warning' => 'Wednesday',
                        'danger' => 'Thursday',
                        'secondary' => 'Friday',
                        'info' => 'Saturday',
                        'primary' => 'Sunday',
                    ]),
                
                Tables\Columns\TextColumn::make('broadcast_time')
                    ->label('Jam Tayang')
                    ->formatStateUsing(fn (?string $state): string => $state ? substr($state, 0, 5) : 'TBA')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('next_episode_date')
                    ->label('Episode Berikutnya')
                    ->date('d/m/Y')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('day_of_week')
                    ->label('Hari')
                    ->options([
                        'Monday' => 'Senin',
                        'Tuesday' => 'Selasa',
                        'Wednesday' => 'Rabu',
                        'Thursday' => 'Kamis',
                        'Friday' => 'Jumat',
                        'Saturday' => 'Sabtu',
                        'Sunday' => 'Minggu',
                    ]),
                
                Tables\Filters\Filter::make('is_active')
                    ->label('Hanya Aktif')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('day_of_week', 'asc');
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
            'index' => Pages\ListSchedules::route('/'),
            'create' => Pages\CreateSchedule::route('/create'),
            'edit' => Pages\EditSchedule::route('/{record}/edit'),
        ];
    }    
}
