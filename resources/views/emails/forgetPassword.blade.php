<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ config('app.name') }}</title>
</head>
<body>
Hello,

<p>We received a request to reset your password for your {{ config('app.name') }} account.</p>

<p>Click the link below to set a new password:</p>
<a href="{{ route('password.reset', $token) }}">Reset Password</a><br>

<p>If you didn't request a password reset, you can safely ignore this email.</p>


<p>Thanks,  <br>
The {{ config('app.name') }} Team</p>
</body>
</html>
