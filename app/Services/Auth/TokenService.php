<?php

namespace App\Services\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Validation\ValidationException;

class TokenService
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Requête générique au serveur OAuth
     */
    public function requestToken(array $params)
    {
        try {
            $response = $this->client->post($this->resolveTokenUrl(), [
                'form_params' => array_merge($params, [
                    'client_id' => config('passport.password_grant_client.id'),
                    'client_secret' => config('passport.password_grant_client.secret'),
                ]),
            ]);

            return json_decode((string) $response->getBody(), true);
        } catch (GuzzleException $e) {
            throw ValidationException::withMessages([
                'token' => ['Erreur lors de la gestion du token.'],
            ]);
        }
    }

    public function passwordGrant(string $email, string $password)
    {
        return $this->requestToken([
            'grant_type' => 'password',
            'username' => $email,
            'password' => $password,
            'scope' => '',
        ]);
    }

    public function refreshGrant(string $refreshToken)
    {
        return $this->requestToken([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'scope' => '',
        ]);
    }

    private function resolveTokenUrl(): string
    {
        $configuredUrl = trim((string) config('passport.token_url', ''));
        if ($configuredUrl !== '') {
            return rtrim($configuredUrl, '/');
        }

        $appUrl = rtrim((string) config('app.url'), '/');

        // `php artisan serve` expose souvent l'app sur :8000 alors que APP_URL
        // reste à `http://localhost` en local.
        if (app()->environment('local') && preg_match('#^http://localhost$#', $appUrl)) {
            return 'http://127.0.0.1:8000/oauth/token';
        }

        return $appUrl.'/oauth/token';
    }
}
