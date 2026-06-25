<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        RateLimiter::clear('register|127.0.0.1');
        RateLimiter::clear('test@example.com|127.0.0.1');
        RateLimiter::clear('test@example.com|127.0.0.1|lock');

        $email = 'test@example.com';

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => $email,
            'whatsapp' => '+6281234567890',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => $email]);
        $response->assertRedirect(route('client.dashboard', absolute: false));
    }
}
