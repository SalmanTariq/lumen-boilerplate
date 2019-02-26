@component('mail::message')
# Reset Password

You're receiving this email because a password reset request was sent from your account.

@component('mail::button', ['url' => $url])
Reset Password
@endcomponent

Thanks, <br>
{{env('APP_NAME')}}

@endcomponent
