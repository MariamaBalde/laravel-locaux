<?php

/**
 * 🚀 Mail Provider: BREVO
 *
 * Cette notification utilise Brevo SMTP pour l'envoi d'emails
 * Configuration: smtp-relay.brevo.com:587 (TLS)
 *
 * @see https://app.brevo.com for dashboard and settings
 */

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    protected Order $order;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->greeting('Bonjour '.$notifiable->name.'!')
            ->subject('Votre commande #'.$this->order->order_number.' a été confirmée')
            ->line('Merci pour votre achat!')
            ->line('Voici les détails de votre commande:')
            ->line('📌 **Numéro de commande**: '.$this->order->order_number)
            ->line('💰 **Montant total**: '.number_format($this->order->total, 2, ',', ' ').' XOF')
            ->line('📍 **Adresse de livraison**: '.$this->order->shipping_address)
            ->line('📦 **Nombre d\'articles**: '.$this->order->items->count())
            ->line('')
            ->line('**Détail des articles:**')
            ->line($this->getItemsDetails())
            ->line('⏱️ **Vous recevrez bientôt un email avec le numéro de suivi.**')
            ->action('Voir mes commandes', url(env('FRONTEND_URL').'/orders/'.$this->order->id))
            ->line('Cordialement,')
            ->line('L\'équipe AfriShop');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total' => $this->order->total,
        ];
    }

    /**
     * Format order items for display
     */
    private function getItemsDetails(): string
    {
        $details = '';
        foreach ($this->order->items as $item) {
            $details .= "• {$item->product->name} x{$item->quantity} - ".
                        number_format($item->price * $item->quantity, 2, ',', ' ').' XOF'."\n";
        }

        return $details;
    }
}
