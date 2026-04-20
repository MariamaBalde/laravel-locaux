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

class VendorNewOrder extends Notification implements ShouldQueue
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
            ->subject('🎉 Vous avez reçu une nouvelle commande!')
            ->line('Un client vient de passer une commande pour vos produits.')
            ->line('**Détails de la commande:**')
            ->line('📌 **Numéro de commande**: '.$this->order->order_number)
            ->line('👤 **Client**: '.$this->order->user->name)
            ->line('📧 **Email client**: '.$this->order->user->email)
            ->line('📞 **Téléphone client**: '.$this->order->user->phone)
            ->line('💰 **Montant total**: '.number_format($this->order->total, 2, ',', ' ').' XOF')
            ->line('📍 **Adresse de livraison**: '.$this->order->shipping_address)
            ->line('')
            ->line('**Produits à expédier:**')
            ->line($this->getVendorItems($notifiable))
            ->line('')
            ->line('✅ **À faire:**')
            ->line('1. Vérifier la disponibilité des produits')
            ->line('2. Préparer la livraison')
            ->line('3. Ajouter un numéro de suivi')
            ->action('Voir la commande', url(env('FRONTEND_URL').'/vendeur/orders/'.$this->order->id))
            ->line('Merci de votre professionnel!')
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
            'customer_name' => $this->order->user->name,
        ];
    }

    /**
     * Get only items from this vendor
     */
    private function getVendorItems($vendeur): string
    {
        $details = '';
        $vendorItems = $this->order->items->filter(function ($item) use ($vendeur) {
            return $item->product->vendeur_id === $vendeur->id;
        });

        foreach ($vendorItems as $item) {
            $details .= "• {$item->product->name} x{$item->quantity} - ".
                        number_format($item->price * $item->quantity, 2, ',', ' ').' XOF'."\n";
        }

        return $details;
    }
}
