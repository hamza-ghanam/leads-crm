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

    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">

    <style>
        :root {
            color-scheme: light;
        }

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

        h1 {
            margin: 0 0 6px;
            font-size: 28px;
        }

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
        <p class="mb-0">
            For companies, please upload trade license, and fill out the license number. <br/>
            For individuals, please upload your ID document.
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
          action="{{ route('brokers.register', ['token' => $token]) }}"
          enctype="multipart/form-data"
          class="mt-3"
          novalidate>
        @csrf

        {{-- Type --}}
        <div class="mb-3">
            <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
            <select id="type" name="type" class="form-select" required>
                <option value="" {{ old('type') === null ? 'selected' : '' }}>Select type</option>
                <option value="Company" {{ old('type') === 'Company' ? 'selected' : '' }}>Company</option>
                <option value="Individual" {{ old('type') === 'Individual' ? 'selected' : '' }}>Individual</option>
            </select>
            @error('type')
            <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="row g-3">
            {{-- Full Name --}}
            <div class="col-12 col-md-6">
                <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                <input id="full_name" name="full_name" type="text" class="form-control"
                       value="{{ old('full_name') }}" required>
                @error('full_name')
                <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- Phone --}}
            <div class="col-12 col-md-6">
                <label for="phone" class="form-label">Phone</label>
                <input id="phone" name="phone" type="text" class="form-control"
                       value="{{ old('phone') }}" placeholder="+962...">
                @error('phone')
                <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>
        </div>

        {{-- Email --}}
        <div class="mt-3">
            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
            <input id="email" name="email" type="email" class="form-control"
                   value="{{ old('email') }}" required>
            @error('email')
            <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        {{-- Nationality --}}
        <div class="mt-3">
            <label for="nationality" class="form-label">Nationality <span class="text-danger">*</span></label>
            <select id="nationality"
                    name="nationality"
                    class="form-select"
                    required>
                <option value="">Select nationality</option>
                @foreach ($countries as $c)
                    <option value="{{ $c->id }}" {{ old('nationality') == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}
                    </option>
                @endforeach
            </select>
            @error('nationality')
            <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        {{-- Address --}}
        <div class="mt-3">
            <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
            <input id="address" name="address" type="text" class="form-control"
                   value="{{ old('address') }}" required>
            @error('address')
            <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        {{-- Company-only fields --}}
        <div id="company_fields" class="d-none mt-3">
            <div class="mb-3">
                <label for="company_name" class="form-label">Company Name <span class="text-danger">*</span></label>
                <input id="company_name" name="company_name" type="text" class="form-control"
                       value="{{ old('company_name') }}">
                @error('company_name')
                <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="license_number" class="form-label">License Number <span class="text-danger">*</span></label>
                <input id="license_number" name="license_number" type="text" class="form-control"
                       value="{{ old('license_number') }}">
                @error('license_number')
                <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="trade_license" class="form-label">Trade License <span class="text-danger">*</span></label>
                <input id="trade_license" name="trade_license" type="file" class="form-control"
                       accept=".pdf,.jpg,.jpeg,.png">
                <div class="form-text">Allowed: PDF, JPG, PNG. Max 2MB.</div>
                @error('trade_license')
                <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>
        </div>

        {{-- ID Type + ID Document (Always required) --}}
        <div id="id_fields" class="mt-3">
            <div class="mb-3">
                <label for="id_type" class="form-label">ID Type <span class="text-danger">*</span></label>
                <select id="id_type" name="id_type" class="form-select" required>
                    <option value="" {{ old('id_type') ? '' : 'selected' }}>Select ID type</option>
                    <option value="ID" {{ old('id_type') === 'ID' ? 'selected' : '' }}>ID</option>
                    <option value="Passport" {{ old('id_type') === 'Passport' ? 'selected' : '' }}>Passport</option>
                </select>
                @error('id_type')
                <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            {{-- ID Number --}}
            <div class="mb-3">
                <label for="id_number" class="form-label">ID Number <span class="text-danger">*</span></label>
                <input
                    id="id_number"
                    name="id_number"
                    type="text"
                    class="form-control"
                    value="{{ old('id_number') }}"
                    placeholder="Enter ID / Passport number"
                    required
                >
                @error('id_number')
                <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="id_document" class="form-label">
                    <span id="id_document_label">ID Document</span> <span class="text-danger">*</span>
                </label>
                <input id="id_document" name="id_document" type="file" class="form-control"
                       accept=".pdf,.jpg,.jpeg,.png" required>
                <div class="form-text" id="id_document_help">Allowed: PDF, JPG, PNG. Max 2MB.</div>
                @error('id_document')
                <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>
        </div>

        <!--
        <div class="row g-3 mt-1">
            {{-- Password --}}
        <div class="col-12 col-md-6">
            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
            <input id="password" name="password" type="password" class="form-control" required>
@error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

{{-- Password Confirmation --}}
        <div class="col-12 col-md-6">
            <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required>
        </div>
    </div>
    *-->

        <button class="btn btn-primary mt-4" type="submit">Register</button>
    </form>
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

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<script>
    new TomSelect('#nationality', {
        placeholder: 'Select nationality',
        allowEmptyOption: true,
        maxOptions: 500,
        create: false,
        sortField: {
            field: 'text',
            direction: 'asc'
        }
    });

    document.getElementById('y').textContent = new Date().getFullYear();

    const typeEl = document.getElementById('type');
    const companyWrap = document.getElementById('company_fields');

    const companyNameEl = document.getElementById('company_name');
    const licenseNumberEl = document.getElementById('license_number');
    const tradeLicenseEl = document.getElementById('trade_license');

    // ID fields (always required)
    const idTypeEl = document.getElementById('id_type');
    const idDocEl = document.getElementById('id_document');
    const idDocLabelEl = document.getElementById('id_document_label');
    const idDocHelpEl = document.getElementById('id_document_help');

    const idNumberEl = document.getElementById('id_number');

    function updateIdNumberPlaceholder() {
        if (!idTypeEl || !idNumberEl) return;

        idNumberEl.placeholder =
            idTypeEl.value === 'Passport'
                ? 'Enter passport number'
                : 'Enter national ID number';
    }

    idTypeEl.addEventListener('change', updateIdNumberPlaceholder);
    updateIdNumberPlaceholder();

    function setRequired(el, isRequired) {
        if (!el) return;
        el.required = !!isRequired;
    }

    function toggleFields() {
        const val = typeEl.value;
        const isCompany = val === 'Company';

        // Company fields show/hide
        companyWrap.classList.toggle('d-none', !isCompany);

        // Company required rules
        setRequired(companyNameEl, isCompany);
        setRequired(licenseNumberEl, isCompany);
        setRequired(tradeLicenseEl, isCompany);

        // ID Document ALWAYS required (regardless of type)
        setRequired(idTypeEl, true);
        setRequired(idDocEl, true);

        // Optional: clear company file when hidden
        if (!isCompany && tradeLicenseEl) tradeLicenseEl.value = '';
    }

    function updateIdDocumentCopy() {
        const t = idTypeEl?.value;
        const label = (t === 'Passport') ? 'Passport Document' : 'ID Document';

        if (idDocLabelEl) idDocLabelEl.textContent = label;

        // You can also tweak help text if you want:
        if (idDocHelpEl) idDocHelpEl.textContent = 'Allowed: PDF, JPG, PNG. Max 2MB.';
    }

    typeEl.addEventListener('change', toggleFields);
    idTypeEl?.addEventListener('change', updateIdDocumentCopy);

    toggleFields();         // On load
    updateIdDocumentCopy(); // On load
</script>
</body>
</html>
