<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewContactMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ContactMessage $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('New contact message - '.config('app.name'))
            ->greeting('A new contact message has been received.')
            ->line('Name: '.$this->message->name)
            ->line('Email: '.$this->message->email);

        if ($this->message->phone) {
            $mail->line('Phone: '.$this->message->phone);
        }

        if ($this->message->subject) {
            $mail->line('Subject: '.$this->message->subject);
        }

        $mail->line('Message: '.$this->message->message)
            ->line('Received: '.$this->message->created_at->format('M j, Y g:i A'))
            ->action('View Message', route('admin.contact-messages.show', $this->message))
            ->replyTo($this->message->email);

        return $mail;
    }
}
