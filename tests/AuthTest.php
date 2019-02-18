<?php

use App\User;
use App\Mail\ResetPasswordEmail;
use Illuminate\Support\Facades\Mail;
use Laravel\Lumen\Testing\DatabaseMigrations;

class AuthTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function login_requires_both_email_and_password()
    {
        $this->withExceptionHandling();

        $this->json('POST', '/auth/login', ['password' => 'secret'])->seeJson([
            'email' => ['The email field is required.']
        ]);

        $this->json('POST', '/auth/login', ['email' => 'secret@asd.com'])->seeJson([
            'password' => ['The password field is required.']
        ]);
    }

    /** @test */
    public function login_requires_correct_credentials()
    {
        // With incorrect email
        $this->json('POST', '/auth/login', ['email' => 'asdsa@asdlk.com', 'password' => 'secret'])
            ->seeStatusCode(401);
    }

    /** @test */
    public function a_user_can_login()
    {
        $user = create('App\User');

        $this->json('POST', '/auth/login', ['email' => $user->email, 'password' => 'secret'])
            ->seeStatusCode(200);
        $token = $this->response->headers->all()['token'][0];

        $this->json('GET', 'check-auth', [], ['Authorization' => "Bearer $token"])
            ->seeStatusCode(200);
    }
    /** @test */
    public function a_user_can_register()
    {
        $user = make('App\User');
        $password = 'secret';

        $this->json('POST', '/auth/register', array_merge($user->toArray(), compact('password')))
            ->seeStatusCode(200);
        $this->seeInDatabase('users', ['full_name' => $user->full_name]);
    }

    /** @test */
    public function user_emails_are_unique()
    {
        $user = create('App\User');
        $password = 'secret';

        $this->withExceptionHandling()->json('POST', '/auth/register', array_merge($user->toArray(), compact('password')))
            ->seeStatusCode(422);
    }

    /** @test */
    public function an_email_can_be_sent_for_resetting_password()
    {
        Mail::fake();
        Mail::assertNothingSent();

        $user = create('App\User');

        $this->json('POST', '/auth/forgot-password', ['email' => $user->email])
            ->seeStatusCode(200);

        Mail::assertSent(ResetPasswordEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    /** @test */
    public function a_registered_user_can_reset_own_password()
    {
        // Disable Emails
        Mail::fake();
        Mail::assertNothingSent();

        // Create User

        $user = create('App\User');

        $newPassword = 'secret123';

        $this->json('POST', '/auth/forgot-password', ['email' => $user->email])
            ->seeStatusCode(200);

        $user = User::find($user->id);

        $this->json('POST', '/auth/reset-password', ['token' => $user->api_token, 'new_password' => $newPassword, 'new_password_confirmation' => $newPassword])
            ->seeStatusCode(200);

        // Check login with new password
        $this->json('POST', '/auth/login', ['email' => $user->email, 'password' => $newPassword])
            ->seeStatusCode(200);
        $token = $this->response->headers->all()['token'][0];

        $this->json('GET', 'check-auth', [], ['Authorization' => "Bearer $token"])
            ->seeStatusCode(200);
    }

    /** @test */
    public function a_valid_token_is_required_to_reset_password()
    {
         // Disable Emails
        Mail::fake();
        Mail::assertNothingSent();

        // Create User

        $user = create('App\User');

        $newPassword = 'secret123';

        $this->withExceptionHandling()->json('POST', '/auth/reset-password', ['token' => str_random(40), 'new_password' => $newPassword, 'new_password_confirmation' => $newPassword])->seeStatusCode(422);
    }

    // /** @test */
    // public function a_user_can_logout()
    // {
    //     $this->json('POST', '/auth/logout', [], ['Authorization' => "Bearer token"])->seeStatusCode(401);

    //     $user = create('App\User');
    //     // Check login with new password
    //     $this->json('POST', '/auth/login', ['email' => $user->email, 'password' => 'secret'])
    //         ->seeStatusCode(200);
    //     $token = $this->response->headers->all()['token'][0];

    //     $this->json('GET', 'check-auth', [], ['Authorization' => "Bearer $token"])
    //         ->seeStatusCode(200);

    //     $this->json('POST', '/auth/logout', [], ['Authorization' => "Bearer $token"]);
    //         // ->json('POST', '/auth/logout', [], ['Authorization' => "Bearer token"])->seeStatusCode(401);
    //     $this->json('GET', 'check-auth', ['Authorization' => "Bearer token"])
    //         ->seeStatusCode(200);
    //     // $this->json('GET', 'check-auth', [], ['Authorization' => "Bearer $token"])
    //     //     ->seeStatusCode(401);
    // }
}
