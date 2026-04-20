<?php

namespace App\Services\Auth;

use GuzzleHttp\Client;
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
            $response = $this->client->post(config('app.url').'/oauth/token', [
                'form_params' => array_merge($params, [
                    'client_id' => config('passport.password_grant_client.id'),
                    'client_secret' => config('passport.password_grant_client.secret'),
                ]),
            ]);

            return json_decode((string) $response->getBody(), true);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
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
}
