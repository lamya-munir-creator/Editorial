<?php

namespace Tests\Feature\Auth;

use App\Mail\SendOtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OtpAuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_can_send_otp_for_new_user(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/register', [
            'first_name' => 'Ahmed',
            'last_name' => 'Ali',
            'username' => 'ahmedali',
            'email' => 'ahmed@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', true)
            ->assertJsonPath('message', 'Please verify your email with the OTP code sent to your inbox.');

        $user = User::query()->where('email', 'ahmed@example.com')->firstOrFail();
        $this->assertNotNull($user->otp_code);
        $this->assertNull($user->email_verified_at);

        Mail::assertSent(SendOtpCode::class, function (SendOtpCode $mail) use ($user): bool {
            return $mail->hasTo($user->email) && $mail->otp === $user->otp_code;
        });
    }

    public function test_verifying_email_otp_returns_access_token(): void
    {
        $user = User::factory()->create([
            'email' => 'verify@example.com',
            'otp_code' => '123456',
            'otp_expires_at' => now()->addMinutes(10),
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/verify-email-otp', [
            'email' => $user->email,
            'code' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'message',
                'data' => ['user', 'access_token', 'token_type'],
            ]);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_resending_otp_refreshes_code_and_sends_new_email(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'resend@example.com',
            'otp_code' => '111111',
            'otp_expires_at' => now()->subMinute(),
        ]);

        $response = $this->postJson('/api/resend-otp', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'A new OTP code has been sent to your email.');

        $user->refresh();
        $this->assertNotSame('111111', $user->otp_code);
        $this->assertTrue($user->otp_expires_at->isFuture());

        Mail::assertSent(SendOtpCode::class);
    }
}
