<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_always_returns_success_response(): void
    {
        User::factory()->create([
            'email' => 'client@example.com',
        ]);

        $response = $this->postJson('/api/password/forgot', [
            'email' => 'client@example.com',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_reset_password_with_invalid_token_returns_422(): void
    {
        User::factory()->create([
            'email' => 'client2@example.com',
        ]);

        $response = $this->postJson('/api/password/reset', [
            'email' => 'client2@example.com',
            'token' => 'invalid-token',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
