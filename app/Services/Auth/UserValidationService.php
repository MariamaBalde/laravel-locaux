<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Service pour les vérifications d'utilisateur (DRY)
 */
class UserValidationService
{
    /**
     * Vérifier si l'utilisateur peut se connecter
     */
    public function validateUserLoginStatus(User $user): void
    {
        if (is_null($user->email_verified_at)) {
            throw ValidationException::withMessages([
                'email' => ['Veuillez vérifier votre adresse email avant de vous connecter.'],
            ]);
        }

        if ($user->statut !== 'actif') {
            throw ValidationException::withMessages([
                'email' => ['Votre compte a été '.$user->statut.'. Contactez l\'administrateur.'],
            ]);
        }
    }
}
