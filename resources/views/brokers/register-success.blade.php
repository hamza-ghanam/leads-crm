<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Broker Registration - {{ config('app.name') }}</title>
    <meta name="description" content="Privacy Policy for our Facebook Lead Ads integration and CRM processing."/>

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
    </style>
</head>
<body>
<header>
    <main style="padding:0;max-width:900px">
        <img src="{{ asset('dist/img/app_logo.png') }}?v={{ config('app.build_version') }}"
             alt="{{ config('app.name') }}" width="200" class="mb-4"/><br/>
        <br/>
        <h1>Broker Registration</h1>
    </main>
</header>

<main>
    <div class="box mb-4">
        <h1 class="text-success">Registration Successful</h1>
        <p>Your registration has been completed successfully.</p>
        <p>You may now close this page.</p>
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
</main>

<footer>
    <main style="padding:0;max-width:900px">
        <p class="mb-0">WRS AE © <span id="y"></span> All rights reserved.</p>
    </main>
</footer>

<!-- Bootstrap 5 Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

<script>
    document.getElementById('y').textContent = new Date().getFullYear();

    const typeEl = document.getElementById('type');
    const companyWrap = document.getElementById('company_fields');
    const individualWrap = document.getElementById('individual_fields');

    const companyNameEl = document.getElementById('company_name');
    const licenseNumberEl = document.getElementById('license_number');
    const tradeLicenseEl = document.getElementById('trade_license');

    const idDocEl = document.getElementById('id_document');

    function setRequired(el, isRequired) {
        if (!el) return;
        el.required = !!isRequired;
    }

    function toggleFields() {
        const val = typeEl.value;

        const isCompany = val === 'Company';
        const isIndividual = val === 'Individual';

        // Show/hide wrappers (Bootstrap 5)
        companyWrap.classList.toggle('d-none', !isCompany);
        individualWrap.classList.toggle('d-none', !isIndividual);

        // Required rules
        setRequired(companyNameEl, isCompany);
        setRequired(licenseNumberEl, isCompany);
        setRequired(tradeLicenseEl, isCompany);
        setRequired(idDocEl, isIndividual);

        // Optional: clear file inputs when hidden
        if (!isCompany && tradeLicenseEl) tradeLicenseEl.value = '';
        if (!isIndividual && idDocEl) idDocEl.value = '';
    }

    typeEl.addEventListener('change', toggleFields);
    toggleFields(); // On load (supports old('type'))
</script>
</body>
</html>
