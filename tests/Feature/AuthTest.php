<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_requires_authentication(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
            'country' => 'SN',
            'statut' => 'actif',
        ]);

        Passport::actingAs($user);

        $response = $this->getJson('/api/auth/me');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'client@test.local',
            'password' => bcrypt('ValidPassword123!'),
            'role' => 'client',
            'country' => 'SN',
            'statut' => 'actif',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'client@test.local',
            'password' => 'WrongPassword!',
        ]);

        $response
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }
}
