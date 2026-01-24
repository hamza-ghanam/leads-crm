<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Upload Signed Agreement - {{ config('app.name') }}</title>
    <meta name="description" content="Upload signed agreement with OTP verification."/>

    <!-- Bootstrap 5 (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <style>
        :root { color-scheme: light; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            color: #111;
            background: #fff;
        }

        header {
            padding: 32px 20px;
            border-bottom: 1px solid #eaeaea;
            background: #fafafa;
        }

        main {
            max-width: 900px;
            margin: 0 auto;
            padding: 28px 20px 48px;
        }

        h1 { margin: 0 0 6px; font-size: 28px; }

        footer {
            border-top: 1px solid #eaeaea;
            padding: 18px 20px;
            color: #666;
            font-size: 14px;
        }

        .box {
            border: 1px solid #eaeaea;
            border-radius: 10px;
            padding: 14px 16px;
            background: #fff;
        }

        .otp {
            letter-spacing: .35em;
            font-weight: 700;
            text-align: center;
            font-variant-numeric: tabular-nums;
        }
    </style>
</head>
<body>
<header>
    <main style="padding:0;max-width:900px">
        <img src="{{ asset('dist/img/app_logo.png') }}?v={{ config('app.build_version') }}"
             alt="{{ config('app.name') }}" width="200" class="mb-4"/><br/>
        <br/>
        <h1>Upload Signed Agreement</h1>
    </main>
</header>

<main>
    <div class="box mb-4">
        <p class="mb-0">
            Please enter your email, the 6-digit OTP you received, and upload the signed agreement file, then submit.
        </p>
    </div>

    {{-- Server-side validation summary (optional) --}}
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>Please fix the following:</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ route('brokers.agreement.upload') }}"
          enctype="multipart/form-data"
          class="mt-3"
          novalidate>
        @csrf

        {{-- Email --}}
        <div class="mb-3">
            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
            <input id="email" name="email" type="email" class="form-control"
                   value="{{ old('email') }}" required>
            @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        {{-- OTP (6 digits) --}}
        <div class="mb-3">
            <label for="otp" class="form-label">OTP <span class="text-danger">*</span></label>
            <input id="otp" name="otp" type="text" class="form-control otp"
                   inputmode="numeric"
                   autocomplete="one-time-code"
                   maxlength="6"
                   minlength="6"
                   pattern="\d{6}"
                   placeholder="______"
                   value="{{ old('otp') }}"
                   required>
            <div class="form-text">Enter the 6-digit code (numbers only).</div>
            @error('otp') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        {{-- Signed Agreement Upload --}}
        <div class="mb-3">
            <label for="signed_agreement" class="form-label">Signed Agreement <span class="text-danger">*</span></label>
            <input id="signed_agreement" name="signed_agreement" type="file" class="form-control"
                   accept=".pdf,.jpg,.jpeg,.png"
                   required>
            <div class="form-text">Allowed: PDF / JPG / PNG. Max 5MB.</div>
            @error('signed_agreement') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        <button class="btn btn-primary mt-2" type="submit">Submit</button>
    </form>
</main>

<footer>
    <main style="padding:0;max-width:900px">
        <p class="mb-0">{{ config('app.name') }} © <span id="y"></span> All rights reserved.</p>
    </main>
</footer>

<!-- Bootstrap 5 Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

<script>
    document.getElementById('y').textContent = new Date().getFullYear();

    // Keep OTP numeric-only (UX helper)
    const otpEl = document.getElementById('otp');
    otpEl.addEventListener('input', function () {
        this.value = (this.value || '').replace(/\D/g, '').slice(0, 6);
    });
</script>
</body>
</html>
