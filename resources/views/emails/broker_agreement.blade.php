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
        <h1>Broker Agency Agreement</h1>
    </div>

    <div class="content">
        <p>Dear {{ $user->full_name }},</p>

        <p>Thank you for registering as a broker with us. Please find attached your official <span class="highlight">Broker Agency Agreement</span> for your review and signature.</p>
        <p>Please use the following verification Code:</p>

        <blockquote style="font-size: 22pt; color: navy; text-align: center;">
            {{ $otp }}
        </blockquote>

        <p>Once signed, please press on the following button to upload the signed copy:</p>

        <p style="text-align:center; margin: 22px 0;">
            <a class="btn" href="{{ route('brokers.agreement.form') }}">
                Upload Signed Agreement
            </a>
        </p>

        <p>If the button above doesn't work, copy and paste the following link into your browser:</p>
        <blockquote cite="{{ config('app.url') }}/brokers/upload-signed-agreement">
            {{ config('app.url') }}/brokers/upload-signed-agreement
        </blockquote>

        <p>If you have any questions or require further assistance, please don’t hesitate to contact our sales team.</p>

        <p>Best regards,<br>
            <strong>Sales Team</strong></p>
    </div>

    <div class="footer">
        &copy; {{ now()->year }} {{ config('app.name') }}. All rights reserved.
    </div>
</div>
</body>
</html>
