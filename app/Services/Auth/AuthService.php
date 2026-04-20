<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\Vendeur;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService implements AuthServiceInterface
{
    private UserValidationService $validationService;

    private TokenService $tokenService;

    public function __construct(
        UserValidationService $validationService,
        TokenService $tokenService
    ) {
        $this->validationService = $validationService;
        $this->tokenService = $tokenService;
    }

    /**
     * Inscription d'un nouvel utilisateur avec transaction
     */
    public function register(array $data): array
    {
        // Utiliser une transaction pour garantir l'atomicité
        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'country' => $data['country'],
                'statut' => 'actif',
                'email_verified_at' => null,
            ]);

            if ($data['role'] === 'vendeur') {
                Vendeur::create([
                    'user_id' => $user->id,
                    'shop_name' => $data['shop_name'],
                    'description' => $data['shop_description'] ?? null,
                    'verified' => false,
                    'rating' => 0,
                    'total_sales' => 0,
                ]);
            }

            return $user;
        });

        $verificationSent = false;
        try {
            if (! $user->hasVerifiedEmail()) {
                $user->sendEmailVerificationNotification();
                $verificationSent = true;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return [
            'user' => $user->load('vendeur'),
            'requires_email_verification' => true,
            'verification_sent' => $verificationSent,
        ];
    }

    /**
     * Connexion d'un utilisateur
     */
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Cet email n\'existe pas dans notre système.'],
            ]);
        }

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'password' => ['Le mot de passe est incorrect.'],
            ]);
        }

        $user = Auth::user();
        $this->validationService->validateUserLoginStatus($user);

        $tokenResult = $user->createToken('Personal Access Token');

        return $this->formatAuthResponse($user, $tokenResult);
    }

    /**
     * Déconnexion d'un utilisateur
     */
    public function logout(User $user): array
    {
        $user->token()?->revoke();

        return ['message' => 'Déconnexion réussie'];
    }

    /**
     * Récupérer l'utilisateur authentifié
     */
    public function me(User $user): User
    {
        return $user->load('vendeur');
    }

    /**
     * Rafraîchir le token
     */
    public function refresh(string $refreshToken): array
    {
        return $this->tokenService->refreshGrant($refreshToken);
    }

    /**
     * Format la réponse d'authentification (DRY)
     */
    private function formatAuthResponse(User $user, $tokenResult): array
    {
        return [
            'user' => $user->load('vendeur'),
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => $tokenResult->token->expires_at,
        ];
    }
}
