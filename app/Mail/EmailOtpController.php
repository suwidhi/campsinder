<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
$environment = App::environment();

class EmailOtpController extends Mailable
{
    use Queueable, SerializesModels;

    public $title;
    public $body;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($details)
    {
        $this->title = $details['title'];
        $this->body = $details['body'];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Akun Verifikasi CAMPSINDER')
                    ->view('email.emailVerification');
    }
}
