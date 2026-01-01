<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpVerificationMail extends Mailable
{
    // Cukup panggil nama belakangnya saja karena sudah di 'use' di atas
    use Queueable, SerializesModels;

    public $otp;
    public $user;

    public function __construct($user, $otp)
    {
        $this->user = $user;
        $this->otp = $otp;
    }

    public function build()
    {
        return $this->subject('Verifikasi Email NipNime')
            ->markdown('emails.otp_verification');
    }
}