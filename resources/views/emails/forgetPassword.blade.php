<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f4f4f5;
        }
        .email-wrapper {
            max-width: 560px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .email-container {
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #3b6f43 0%, #4a8454 100%);
            padding: 24px;
            text-align: center;
        }
        .logo {
            font-size: 18px;
            font-weight: 600;
            color: #ffffff;
            letter-spacing: 0.5px;
        }
        .content {
            padding: 32px 24px;
        }
        .greeting {
            font-size: 15px;
            color: #18181b;
            margin: 0 0 16px 0;
            font-weight: 500;
        }
        .message {
            font-size: 14px;
            color: #52525b;
            line-height: 1.5;
            margin: 0 0 24px 0;
        }
        .button-container {
            text-align: center;
            margin: 32px 0;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #3b6f43 0%, #4a8454 100%);
            color: #ffffff;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .button:hover {
            transform: translateY(-1px);
        }
        .notice {
            font-size: 13px;
            color: #71717a;
            line-height: 1.4;
            margin: 24px 0 0 0;
            padding: 16px;
            background: #f4f4f5;
            border-radius: 6px;
        }
        .footer {
            padding: 16px 24px;
            background: #fafafa;
            border-top: 1px solid #e4e4e7;
            text-align: center;
            font-size: 12px;
            color: #a1a1aa;
        }
        .link-fallback {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #e4e4e7;
            font-size: 12px;
            color: #71717a;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="header">
                <div class="logo">{{ config('app.name') }}</div>
            </div>
            
            <div class="content">
                <div class="greeting">Hello,</div>
                
                <div class="message">
                    We received a request to reset your password for your {{ config('app.name') }} account.
                </div>

                <div class="message">
                    Click the button below to set a new password:
                </div>

                <div class="button-container">
                    <a href="{{ route('password.reset', $token) }}" class="button">
                        Reset Password
                    </a>
                </div>

                <div class="notice">
                    <strong>Security tip:</strong> If you didn't request a password reset, you can safely ignore this email. Your password will remain unchanged.
                </div>

                <div class="link-fallback">
                    If the button doesn't work, copy and paste this link into your browser:<br>
                    <a href="{{ route('password.reset', $token) }}" style="color: #3b6f43;">{{ route('password.reset', $token) }}</a>
                </div>
            </div>

            <div class="footer">
                Automated message — Please do not reply
            </div>
        </div>
    </div>
</body>
</html>
