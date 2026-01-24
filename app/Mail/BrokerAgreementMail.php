<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BrokerAgreementMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $fileName;
    public $otp;

    public $tries = 3;
    public $backoff = [30,120];

    public function __construct($user, $fileName, $otp)
    {
        $this->user = $user;
        $this->fileName = $fileName;
        $this->otp = $otp;
    }

    public function build()
    {
        return $this->subject('Your Broker Agreement')
            ->view('emails.broker_agreement')
            ->attach(storage_path("app/agreements/{$this->fileName}"), [
                'as'   => $this->fileName,
                'mime' => 'application/pdf',
            ]);
    }
}
