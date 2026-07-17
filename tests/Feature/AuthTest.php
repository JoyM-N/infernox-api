<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Run seeders before each test
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    }

    public function test_operator_can_login_with_valid_credentials(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'email'    => 'test@infernox.com',
            'password' => 'password123',
        ]);
        $user->assignRole('operator');

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'test@infernox.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'token',
                     'user' => ['id', 'name', 'email', 'role', 'permissions']
                 ])
                 ->assertJsonPath('message', 'Login successful.');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'test@infernox.com']);

        $this->postJson('/api/auth/login', [
            'email'    => 'test@infernox.com',
            'password' => 'wrongpassword',
        ])->assertStatus(401);
    }

    public function test_login_fails_with_missing_fields(): void
    {
        $this->postJson('/api/auth/login', [
            'email' => 'test@infernox.com',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['password']);
    }

    public function test_authenticated_user_can_get_their_profile(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('operator');

        $this->actingAs($user, 'sanctum')
             ->getJson('/api/auth/me')
             ->assertStatus(200)
             ->assertJsonPath('email', $user->email)
             ->assertJsonPath('role', 'operator');
    }

    public function test_user_can_logout(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
             ->postJson('/api/auth/logout')
             ->assertStatus(200)
             ->assertJsonPath('message', 'Logged out successfully.');
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/auth/me')
             ->assertStatus(401);
    }
}