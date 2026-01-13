<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactMessageResource\Pages;
use App\Models\ContactMessage;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class ContactMessageResource extends Resource
{
    // Use a distinct slug to avoid colliding with legacy admin routes
    protected static ?string $slug = 'contact-messages-filament';
    protected static ?string $model = ContactMessage::class;
    protected static ?string $navigationIcon = 'heroicon-o-mail';
    protected static ?string $navigationLabel = 'Pesan Kontak';
    protected static ?string $navigationGroup = 'User Messages';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->disabled(),
                Forms\Components\TextInput::make('email')->disabled(),
                Forms\Components\TextInput::make('subject')->disabled(),
                Forms\Components\Textarea::make('message')->disabled(),
                Forms\Components\Textarea::make('reply')
                    ->label('Balasan Admin')
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('subject'),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d M Y H:i'),
                Tables\Columns\TextColumn::make('reply')->label('Balasan')->limit(30),
                Tables\Columns\BadgeColumn::make('is_closed')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => $state ? 'Selesai' : 'Menunggu')
                    ->colors(['success' => fn ($state) => $state, 'warning' => fn ($state) => !$state]),
            ])
            ->filters([
                // Tambahkan filter jika perlu
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactMessages::route('/'),
            'edit' => Pages\EditContactMessage::route('/{record}/edit'),
            'view' => Pages\ViewContactMessage::route('/{record}'),
        ];
    }
}
