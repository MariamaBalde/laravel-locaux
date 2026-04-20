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

use App\Models\Vendeur;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorVerificationRequest extends Notification implements ShouldQueue
{
    use Queueable;

    protected Vendeur $vendeur;

    /**
     * Create a new notification instance.
     */
    public function __construct(Vendeur $vendeur)
    {
        $this->vendeur = $vendeur;
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
            ->greeting('Bonjour Admin!')
            ->subject('📋 Un nouveau vendeur demande à être vérifié')
            ->line('Un nouveau vendeur s\'est inscrit sur AfriShop.')
            ->line('**Informations du vendeur:**')
            ->line('👤 **Nom**: '.$this->vendeur->user->name)
            ->line('📧 **Email**: '.$this->vendeur->user->email)
            ->line('📞 **Téléphone**: '.$this->vendeur->user->phone)
            ->line('🏪 **Nom du magasin**: '.$this->vendeur->shop_name)
            ->line('📝 **Description**: '.$this->vendeur->description)
            ->line('📍 **Pays**: '.$this->vendeur->user->country)
            ->line('')
            ->line('**Adresse enregistrée:**')
            ->line($this->vendeur->user->address)
            ->line('')
            ->line('✅ **À faire:**')
            ->line('1. Vérifier les informations du vendeur')
            ->line('2. Vérifier la légalité de la boutique')
            ->line('3. Approuver ou rejeter la demande')
            ->action('Accéder au panneau d\'administration', url(env('FRONTEND_URL').'/admin/vendors/'.$this->vendeur->id))
            ->line('Merci de votre vigilance!')
            ->line('L\'équipe AfriShop');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'vendeur_id' => $this->vendeur->id,
            'shop_name' => $this->vendeur->shop_name,
            'vendor_name' => $this->vendeur->user->name,
        ];
    }
}
