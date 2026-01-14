<?php

namespace App\Filament\Resources\ContactMessageResource\Pages;

use App\Filament\Resources\ContactMessageResource;
use App\Models\ContactReply;
use Filament\Resources\Pages\ViewRecord;
use Filament\Pages\Actions;
use Filament\Notifications\Notification;
use Filament\Forms;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactMessageReplied;

class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    protected static string $view = 'filament.resources.contact-message.view';

    protected function getActions(): array
    {
        return [
            Actions\Action::make('reply')
                ->label('Balas')
                ->icon('heroicon-o-reply')
                ->form([
                    Forms\Components\Textarea::make('reply_text')
                        ->label('Pesan Balasan')
                        ->required()
                        ->rows(4),
                ])
                ->action(function (array $data) {
                    // Create new reply using ContactReply model
                    ContactReply::create([
                        'contact_message_id' => $this->record->id,
                        'message' => $data['reply_text'],
                        'is_admin' => true,
                        'admin_id' => auth()->id(),
                    ]);

                    // Also update the reply field for backward compatibility
                    $this->record->reply = $data['reply_text'];
                    $this->record->replied_at = now();
                    $this->record->save();

                    // Try to send email notification
                    try {
                        Mail::to($this->record->email)->send(new ContactMessageReplied($this->record));
                    } catch (\Exception $e) {
                        logger()->error('Failed to send reply email: ' . $e->getMessage());
                    }

                    Notification::make()->success()->title('Balasan terkirim!')->send();
                })
                ->visible(fn () => !$this->record->is_closed),

            Actions\Action::make('close')
                ->label('Tandai Selesai')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->is_closed = true;
                    $this->record->closed_at = now();
                    $this->record->save();

                    Notification::make()->success()->title('Percakapan ditutup')->send();
                })
                ->visible(fn () => !$this->record->is_closed),

            Actions\Action::make('reopen')
                ->label('Buka Kembali')
                ->icon('heroicon-o-refresh')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->is_closed = false;
                    $this->record->closed_at = null;
                    $this->record->save();

                    Notification::make()->success()->title('Percakapan dibuka kembali')->send();
                })
                ->visible(fn () => $this->record->is_closed),

            Actions\Action::make('open_public')
                ->label('Buka Link User')
                ->icon('heroicon-o-external-link')
                ->url(fn () => $this->record->view_token ? route('contact.status', $this->record->view_token) : null)
                ->openUrlInNewTab()
                ->visible(fn () => !empty($this->record->view_token)),
        ];
    }
}
