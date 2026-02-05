<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailable;

class OtpNotification  extends Mailable
{
    use Queueable, SerializesModels;

    protected $otp;
    protected $user;

    public function __construct($otp, $user = null)
    {
        $this->otp = $otp;
        $this->user = $user;
    }




    public function build()
    {
        return $this->from(config('mail.from.address'), config('mail.from.name'))
                    ->subject('Your OTP Code')
                    ->view('emails.otp')
                    ->with([
                        'otp' => $this->otp,
                        'user' => $this->user,
                        'name' => $this->user ? $this->user->name : 'User',
                    ]);
    }

}
