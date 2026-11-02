<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class AdminPaidOrderNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New paid Sograte order '.$this->order->order_number)
            ->greeting('A customer payment has been confirmed.')
            ->line('Order number: '.$this->order->order_number)
            ->line('Customer name: '.$this->order->customer_name)
            ->line('Customer email: '.$this->order->customer_email)
            ->line('Order total: '.$this->order->total_formatted)
            ->line('Fulfillment: '.$this->order->fulfillment_method->label())
            ->line('Payment status: '.$this->order->payment_status->label())
            ->line('Payment confirmation: Confirmed by Stripe')
            ->action('View Order in Sograte Admin', route('admin.orders.show', $this->order))
            ->salutation('- Sograte');
    }
}
