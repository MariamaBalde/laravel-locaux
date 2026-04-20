<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthPayloadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user' => isset($this['user']) ? new UserResource($this['user']) : null,
            'access_token' => $this['access_token'] ?? null,
            'token_type' => $this['token_type'] ?? null,
            'expires_at' => $this['expires_at'] ?? null,
            'requires_email_verification' => $this['requires_email_verification'] ?? null,
            'verification_sent' => $this['verification_sent'] ?? null,
        ];
    }
}
