<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333333;
            line-height: 1.6;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 30px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: #fafafa;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
        }

        .header img {
            max-height: 80px;
            margin-bottom: 10px;
        }

        .header h1 {
            font-size: 20px;
            margin: 0;
            color: #2c3e50;
        }

        .content {
            font-size: 16px;
        }

        .footer {
            margin-top: 30px;
            font-size: 13px;
            color: #999999;
            text-align: center;
        }

        .highlight {
            color: #2c3e50;
            font-weight: bold;
        }

        blockquote {
            margin: 12px 0;
            padding: 12px 16px;
            background: #fff;
            border-left: 4px solid #2c3e50;
            word-break: break-word;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        {{-- Replace with your actual logo path --}}
        <img
            src="{{ asset('dist/img/app_logo.png') }}"
            alt="{{ config('app.name') }} Logo"
            style="max-width: 200px; height: auto; display: inline-block; margin-bottom: 15px;"
        >
        <h1>Broker Account Registration</h1>
    </div>

    <div class="content">
        <p>🎉 <strong>Congratulations,</strong> {{ $user->full_name }}!.</p>

        <p>We’re pleased to let you know that your registration is done!</p>

        <p>Best regards,</p>
    </div>

    <div class="footer">
        &copy; {{ now()->year }} {{ config('app.name') }}. All rights reserved.
    </div>
</div>
</body>
</html>
