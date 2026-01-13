<?php

namespace App\Filament\Resources\ContactMessageResource\Pages;

use App\Filament\Resources\ContactMessageResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Pages\Actions;
use Filament\Notifications\Notification;
use Filament\Forms;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactMessageReplied;

class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    protected function getActions(): array
    {
        return [
            Actions\Action::make('reply')
                ->label('Balas')
                ->form([
                    Forms\Components\Textarea::make('reply')->required(),
                ])
                ->action(function (array $data, $record) {
                    $record->reply = $data['reply'];
                    $record->replied_at = now();
                    $record->save();

                    try {
                        Mail::to($record->email)->send(new ContactMessageReplied($record));
                    } catch (\Exception $e) {
                        logger()->error('Failed to send reply email: ' . $e->getMessage());
                    }

                    Notification::make()->success()->title('Balasan terkirim')->send();
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->visible(fn ($record) => !$record->is_closed),

            Actions\Action::make('close')
                ->label('Tandai Selesai')
                ->requiresConfirmation()
                ->action(function ($record) {
                    $record->is_closed = true;
                    $record->closed_at = now();
                    $record->save();

                    Notification::make()->success()->title('Percakapan ditutup')->send();
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->visible(fn ($record) => !$record->is_closed),

            Actions\Action::make('open_public')
                ->label('Buka Tautan Pengirim')
                ->url(fn ($record) => $record->view_token ? route('contact.status', $record->view_token) : null)
                ->openUrlInNewTab()
                ->visible(fn ($record) => !empty($record->view_token)),
        ];
    }
}
