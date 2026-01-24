<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OneTimeLinkMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $otl;
    public $token;

    public $tries = 3;
    public $backoff = [30,120];

    public function __construct($otl = null, $token = null)
    {
        $this->otl = $otl;
        $this->token = $token;
    }

    public function build()
    {
        if ($this->otl)
            return $this->subject('Your Registration Link')->view('emails.otl');
        else
            return $this->subject('Account Registration Approved')->view('emails.broker_approved');
    }
}
