<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\ContactMessage;

class ContactMessageReplied extends Mailable
{
    use Queueable, SerializesModels;

    public $messageModel;

    public function __construct(ContactMessage $message)
    {
        $this->messageModel = $message;
    }

    public function build()
    {
        return $this->subject('Balasan dari Tim Support')
            ->view('emails.contact_replied')
            ->with([
                'name' => $this->messageModel->name,
                'reply' => $this->messageModel->reply,
                'link' => route('contact.status', $this->messageModel->view_token),
            ]);
    }
}
