<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            line-height: 1.5;
            color: #333;
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .logo {
            font-size: 20px;
            font-weight: bold;
            color: #3b6f43;
            margin-bottom: 20px;
            text-align: center;
            padding-bottom: 15px;
            border-bottom: 2px solid #3b6f43;
        }
        .otp-box {
            background: linear-gradient(135deg, #3b6f43, #4a8454);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .otp-code {
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 6px;
            color: #ffffff;
            font-family: 'Courier New', monospace;
        }
        .otp-expiry {
            color: rgba(255, 255, 255, 0.9);
            font-size: 12px;
            margin-top: 10px;
        }
        p {
            font-size: 14px;
            margin: 15px 0;
            color: #555;
        }
        .note {
            font-size: 12px;
            color: #888;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">{{ config('app.name') }}</div>
        
        <p>Hello{{ $userName ? ' ' . $userName : '' }},</p>
        
        <p>Your verification code for login is:</p>

        <div class="otp-box">
            <div class="otp-code">{{ $otpCode }}</div>
            <div class="otp-expiry">Expires in {{ $expiresInMinutes }} minutes</div>
        </div>

        <p>Enter this code to complete your sign in. If you didn't request this, please ignore this email.</p>

        <div class="note">
            This is an automated message. Please do not reply.
        </div>
    </div>
</body>
</html>

        <div class="otp-container">
            <div class="otp-label">Your Verification Code</div>
            <div class="otp-code">{{ $otpCode }}</div>
            <div class="otp-expiry">This code will expire in {{ $expiresInMinutes }} minutes</div>
        </div>

        <p class="message">
            Enter this code on the verification page to access your account. For your security, do not share this code with anyone.
        </p>

        <div class="warning">
            <div class="warning-title">⚠️ Security Notice</div>
            <p class="warning-text">
                If you didn't attempt to sign in, please ignore this email and ensure your account password is secure. 
                Someone may have entered your email address by mistake.
            </p>
        </div>

        <div class="footer">
            <p>This is an automated message from <span class="footer-brand">{{ config('app.name') }}</span>.</p>
            <p>Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
