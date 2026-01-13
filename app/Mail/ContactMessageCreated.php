<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\ContactMessage;

class ContactMessageCreated extends Mailable
{
    use Queueable, SerializesModels;

    public $messageModel;

    public function __construct(ContactMessage $message)
    {
        $this->messageModel = $message;
    }

    public function build()
    {
        return $this->subject('Pesan Anda telah diterima')
            ->view('emails.contact_created')
            ->with([
                'name' => $this->messageModel->name,
                'link' => route('contact.status', $this->messageModel->view_token),
            ]);
    }
}
