<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_visitor_can_register_and_is_asked_to_verify_their_email(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Arta Hoxha',
            'email' => 'arta@example.com',
            'password' => 'a-Strong-pass-123',
            'password_confirmation' => 'a-Strong-pass-123',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $user = User::where('email', 'arta@example.com')->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_registration_requires_accepting_the_disclaimer(): void
    {
        $this->post('/register', [
            'name' => 'Arta Hoxha',
            'email' => 'arta@example.com',
            'password' => 'a-Strong-pass-123',
            'password_confirmation' => 'a-Strong-pass-123',
        ])->assertSessionHasErrors('terms');

        $this->assertGuest();
    }

    public function test_a_user_can_log_in_and_is_sent_to_the_app(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('app'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);

        $this->assertGuest();
    }

    public function test_guests_are_redirected_from_the_app_to_login(): void
    {
        $this->get('/app/transactions')->assertRedirect(route('login'));
    }

    public function test_unverified_users_cannot_open_the_app(): void
    {
        $this->actingAs(User::factory()->unverified()->create())
            ->get('/app')
            ->assertRedirect(route('verification.notice'));
    }

    public function test_verified_users_get_the_app_shell_marked_noindex(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/app/budgets')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('<div id="root"', false);
    }

    public function test_the_verification_link_verifies_the_email(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect(route('app'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_the_api_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/api/v1/workspaces')->assertUnauthorized();
    }
}
