<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Privacy Policy</title>
    <meta name="description" content="Privacy Policy for our Facebook Lead Ads integration and CRM processing."/>
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

        .meta {
            margin: 0;
            color: #555;
        }

        h2 {
            margin-top: 26px;
            font-size: 18px;
        }

        p, li {
            color: #222;
        }

        ul {
            padding-left: 20px;
        }

        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 4px;
        }

        footer {
            border-top: 1px solid #eaeaea;
            padding: 18px 20px;
            color: #666;
            font-size: 14px;
        }

        a {
            color: #0a58ca;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
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

        <h1>Privacy Policy</h1>
        <p class="meta"><strong>Last updated:</strong> January 2026</p>
    </main>
</header>
<main>
    <div class="box">
        <p>
            This Privacy Policy explains how we collect, use, store, and protect information obtained through our
            Facebook
            Lead Ads integration and related customer relationship management (CRM) processes.
        </p>
    </div>
    <h2>1. Information We Collect</h2>
    <p>
        We collect information that users voluntarily submit via Facebook Lead Ads forms. Depending on the form, this
        may include:
    </p>
    <ul>
        <li>Name</li>
        <li>Phone number</li>
        <li>Email address</li>
        <li>Responses to custom form questions</li>
        <li>Campaign and form metadata (e.g., form ID, ad ID, timestamps)</li>
    </ul>
    <p>We do not intentionally collect sensitive personal information.</p>

    <h2>2. How We Use the Information</h2>
    <p>We use the collected information solely for legitimate business purposes, such as:</p>
    <ul>
        <li>Contacting users about their enquiry/request</li>
        <li>Providing information or services requested by the user</li>
        <li>Managing, tracking, and following up on leads within our CRM</li>
        <li>Improving internal sales and support workflows</li>
    </ul>

    <h2>3. Legal Basis and Consent</h2>
    <p>
        Lead data is obtained from Facebook only after the user submits a lead form. By submitting a Facebook lead form,
        the user authorises us to receive and process the submitted information for the purposes described in this
        policy.
    </p>

    <h2>4. Data Storage and Security</h2>
    <p>
        We store lead data securely and restrict access to authorised personnel only. We apply reasonable technical and
        organisational
        measures to protect personal data against unauthorised access, disclosure, alteration, or misuse.
    </p>

    <h2>5. Data Sharing</h2>
    <p>
        We do not sell or rent personal data. We do not share lead information with third parties except:
    </p>
    <ul>
        <li>Where required by law, regulation, or legal process</li>
        <li>Where necessary to fulfil the user’s request (e.g., internal authorised teams and systems involved in
            providing the requested service)
        </li>
    </ul>

    <h2>6. Data Retention</h2>
    <p>
        We retain lead data only for as long as necessary to fulfil the purposes described in this policy, or as
        required by applicable laws
        and legitimate business needs. We may retain limited records for audit, dispute resolution, or compliance
        purposes.
    </p>

    <h2>7. User Rights</h2>
    <p>
        Users may request access to, correction of, or deletion of their personal data by contacting us using the
        details below.
        We will respond within a reasonable timeframe and in accordance with applicable laws.
    </p>

    <h2>8. Third-Party Platforms</h2>
    <p>
        This application interacts with Meta/Facebook APIs. Data obtained from Facebook is used strictly in line with
        user consent
        and applicable platform policies.
    </p>

    <h2>9. Contact Information</h2>
    <p>
        If you have any questions or concerns about this Privacy Policy or our data handling practices, please contact:
    </p>
    <ul>
        <li><strong>Email:</strong> <a href="mailto:privacy@wrsae.ae">privacy@wrsae.ae</a></li>
    </ul>
</main>
<footer>
    <main style="padding:0;max-width:900px">
        <p style="margin:0">WRS AE © <span id="y"></span> All rights reserved.</p>
    </main>
</footer>
<script>
    document.getElementById('y').textContent = new Date().getFullYear();
</script>
</body>
</html>

