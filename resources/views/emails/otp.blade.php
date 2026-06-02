<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Code</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f4f4f5;
        }
        .email-wrapper {
            max-width: 480px;
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
        .otp-box {
            background: #f4f4f5;
            border: 2px solid #e4e4e7;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 24px 0;
        }
        .otp-code {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 8px;
            color: #18181b;
            font-family: 'SF Mono', 'Consolas', 'Monaco', monospace;
        }
        .otp-expiry {
            font-size: 12px;
            color: #71717a;
            margin-top: 8px;
        }
        .notice {
            font-size: 13px;
            color: #71717a;
            line-height: 1.4;
            margin: 16px 0 0 0;
        }
        .footer {
            padding: 16px 24px;
            background: #fafafa;
            border-top: 1px solid #e4e4e7;
            text-align: center;
            font-size: 12px;
            color: #a1a1aa;
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
                <div class="greeting">Hello{{ $userName ? ' ' . $userName : '' }},</div>
                
                <div class="message">
                    Use the following code to complete your sign in:
                </div>

                <div class="otp-box">
                    <div class="otp-code">{{ $otpCode }}</div>
                    <div class="otp-expiry">Valid for {{ $expiresInMinutes }} minutes</div>
                </div>

                <div class="notice">
                    If you didn't request this code, please ignore this email.
                </div>
            </div>

            <div class="footer">
                Automated message — Please do not reply
            </div>
        </div>
    </div>
</body>
</html>
