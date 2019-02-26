<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use App\Mail\VerificationEmail;
use App\Mail\ResetPasswordEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller {

    /**
     * Check credentials and log the user in
     * @param  Request $request The request parameters
     * @return \Illuminate\Http\JsonResponse           The response with api token in header
     */
    public function login(Request $request)
    {
        $this->validate($request, [
            'email' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        // Check for valid email
        if (!$user) {
            return response()->json('Invalid Email', 401);
        }

        // Check for valid password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json('Invalid Password', 401);
        }

        // Credentials accepted. Issue tokens
        return $this->authenticate($user);
    }

    /**
     * Check credentials, register user, and log the user in
     * @param  Request $request The request parameters
     * @return \Illuminate\Http\JsonResponse           The response with api token in header
     */
    public function register(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|unique:users,email',
            'password' => 'required',
            'full_name' => 'required',
            'username' => 'required',
        ]);

        $user = new User();
        $user->email = $request->email;
        $user->password = app('hash')->make($request->password);
        $user->full_name = $request->full_name;
        $user->username = $request->username;

        Mail::to($user->email)->send(new VerificationEmail($token = str_random(40)));
        $user->email_token = $token;

        // Credentials accepted. Issue tokens
        return $this->authenticate($user);
    }

    public function verifyEmail(Request $request)
    {
        $this->validate($request, [
            'token' => 'required'
        ]);

        $token = $request->token;
        $user = User::where('email_token', $token)->first();

        if (!$user)
            return response()->json('Invalid token', 422);

        $user->is_email_verified = true;
        $user->email_token = null;
        $user->save();

        return $this->jsonResponse('Email Verified');
    }

    /**
     * Check given email address and send reset password email
     * @param  Request $request The request parameters
     * @return \Illuminate\Http\JsonResponse           The response
     */
    public function forgotPassword(Request $request)
    {
        $this->validate($request, [
            'email' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        $resetToken = base64_encode(str_random(40));
        $user->api_token = $resetToken;

        $user->save();

        Mail::to($user->email)->send(new ResetPasswordEmail($resetToken));

        return response()->json('Email sent');
    }

    public function showResetPasswordForm(Request $request)
    {
        $token = $request->token;
        return view('reset-password');
    }

    /**
     * Check credentials and log the user in
     * @param  Request $request The request parameters
     * @return \Illuminate\Http\JsonResponse           The response with api token in header
     */
    public function resetPassword(Request $request)
    {
        $this->validate($request, [
            'token' => 'required|exists:users,api_token'
        ]);
        $user = User::where('api_token', $request->token)->first();

        $resetToken = $request->token;

        $user->password = app('hash')->make($request->input('new_password'));
        $user->save();

        return response()->json('Password reset');
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json('Logged out');
    }

    /**
     * Generates a token and returns it as header in a JsonResponse
     * @param  User   $user The user to generate the token for.
     * @return Illuminate\Http\JsonResponse       The returned response.
     */
    public function authenticate(User $user)
    {
        $token = $user->createToken($user->email)->accessToken;

        $user->api_token = $token;
        $user->save();

        return response()->json('Logged in', 200, ['token' => $token]);
    }

}
