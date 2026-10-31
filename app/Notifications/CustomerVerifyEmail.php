<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;

class CustomerVerifyEmail extends VerifyEmail
{
    public function toMail($notifiable)
    {
        return parent::toMail($notifiable)
            ->subject('Verify your SOGRATE email address');
    }
}
