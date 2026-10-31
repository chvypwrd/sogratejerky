<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;

class CustomerResetPassword extends ResetPassword
{
    public function toMail($notifiable)
    {
        return parent::toMail($notifiable)
            ->subject('Reset your SOGRATE password');
    }

    protected function resetUrl($notifiable): string
    {
        return route('customer.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}
