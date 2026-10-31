<?php

namespace App\Notifications;

use App\Enums\OrderEvent;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class OrderStatusNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public OrderEvent $event,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subject())
            ->greeting('Hello '.$this->order->customer_name.',')
            ->line('Order reference: '.$this->order->order_number)
            ->line('Order total: '.$this->order->total_formatted);

        match ($this->event) {
            OrderEvent::OrderCreated => $mail
                ->line('Your payment has been confirmed and your order is being prepared.'),
            OrderEvent::ReadyForPickup => $this->addPickupDetails($mail),
            OrderEvent::PickedUp => $mail
                ->line('Your order has been picked up. Thank you for choosing SOGRATE.'),
            OrderEvent::OutForDelivery => $mail
                ->line('Your order is out for delivery.'),
            OrderEvent::Delivered => $mail
                ->line('Your order has been delivered. We hope you enjoy it.'),
            OrderEvent::Shipped => $this->addShippingDetails($mail),
            OrderEvent::OrderCancelled => $this->addCancellationDetails($mail),
        };

        $mail->line('If you have questions about this order, please contact SOGRATE.');

        $trackingUrl = $this->order->tracking_url;

        if ($this->event === OrderEvent::Shipped && $trackingUrl) {
            $mail->action('Track Package', $trackingUrl);
        } elseif ($this->order->user_id) {
            $mail->action('View Order', route('account.orders.show', $this->order));
        } else {
            $mail->action('Contact SOGRATE', route('contact.show'));
        }

        return $mail->salutation('— SOGRATE');
    }

    private function subject(): string
    {
        return match ($this->event) {
            OrderEvent::OrderCreated => 'Your SOGRATE order '.$this->order->order_number.' has been placed',
            OrderEvent::ReadyForPickup => 'Order '.$this->order->order_number.' is ready for pickup',
            OrderEvent::PickedUp => 'Order '.$this->order->order_number.' was picked up',
            OrderEvent::OutForDelivery => 'Order '.$this->order->order_number.' is out for delivery',
            OrderEvent::Delivered => 'Order '.$this->order->order_number.' has been delivered',
            OrderEvent::Shipped => 'Order '.$this->order->order_number.' has shipped',
            OrderEvent::OrderCancelled => 'Order '.$this->order->order_number.' has been cancelled',
        };
    }

    private function addPickupDetails(MailMessage $mail): MailMessage
    {
        $pickup = $this->order->pickup_location_snapshot;

        $mail->line('Your order is ready for pickup.');

        if ($pickup) {
            $mail->line('Pickup location: '.($pickup['name'] ?? 'SOGRATE pickup'));

            if (! empty($pickup['address'])) {
                $mail->line('Address: '.$pickup['address']);
            }

            if (! empty($pickup['contact_details'])) {
                $mail->line('Contact: '.$pickup['contact_details']);
            }

            if (! empty($pickup['instructions'])) {
                $mail->line('Instructions: '.$pickup['instructions']);
            }
        }

        return $mail->line('Please bring your order number when you arrive.');
    }

    private function addShippingDetails(MailMessage $mail): MailMessage
    {
        $mail->line('Your order has shipped.');

        if ($this->order->shipping_carrier) {
            $mail->line('Carrier: '.$this->order->shipping_carrier->label());
        }

        if ($this->order->tracking_number) {
            $mail->line('Tracking number: '.$this->order->tracking_number);
        } else {
            $mail->line('This shipment does not have tracking information.');
        }

        return $mail;
    }

    private function addCancellationDetails(MailMessage $mail): MailMessage
    {
        $mail->line('Your order has been cancelled.');

        if ($this->order->cancellation_reason) {
            $mail->line('Reason: '.$this->order->cancellation_reason);
        }

        return $mail->line('No payment was collected for this order.');
    }
}
