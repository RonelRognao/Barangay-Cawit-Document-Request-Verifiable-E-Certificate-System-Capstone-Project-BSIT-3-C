<!DOCTYPE html>
<html lang="en">
<head>
<link rel="icon" type="image/png" href="{{ asset('assets/barangay_cawit_logo.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Cawit - Certificate Verification</title>

    <style>
        * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            padding: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #e8f0fa, #f8fafc);
            color: #1e293b;
        }

        .container {
            width: 100%;
            max-width: 720px;
            background: #fff;
            padding: 35px;
            border-radius: 18px;
            box-shadow: 0 12px 35px rgba(15, 45, 75, .12);
        }

        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
        }

        .search-box input {
            flex: 1;
            padding: 13px 15px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 14px;
            outline: none;
        }

        .search-box input:focus {
            border-color: #123b68;
            box-shadow: 0 0 0 3px rgba(18, 59, 104, 0.12);
        }

        .search-box button {
            padding: 13px 20px;
            border: none;
            border-radius: 10px;
            background: #123b68;
            color: white;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            white-space: nowrap;
        }

        .search-box button:hover {
            background: #0d2b4d;
        }

        .info-box {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            color: #334155;
            padding: 18px;
            text-align: center;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.6;
        }

        @media (max-width: 550px) {
            .search-box {
                flex-direction: column;
            }
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 22px;
            margin-bottom: 25px;
        }

        .logo {
            width: 95px;
            height: 95px;
            object-fit: contain;
            margin-bottom: 10px;
        }

        .header h1 {
            color: #123b68;
            font-size: 25px;
            margin: 5px 0;
        }

        .header h2 {
            color: #123b68;
            font-size: 18px;
            margin: 8px 0;
        }

        .header p {
            color: #64748b;
            margin: 5px 0;
            font-size: 14px;
        }

        .valid {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
            padding: 20px;
            text-align: center;
            border-radius: 12px;
            font-size: 21px;
            font-weight: bold;
            margin-bottom: 25px;
        }

        .valid small {
            display: block;
            font-size: 13px;
            font-weight: normal;
            margin-top: 7px;
        }

        .invalid {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
            padding: 20px;
            text-align: center;
            border-radius: 12px;
            font-weight: bold;
        }

        .invalid p {
            font-size: 14px;
            font-weight: normal;
        }

        .details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 17px;
            border-radius: 10px;
            overflow-wrap: anywhere;
        }

        .item strong {
            display: block;
            color: #64748b;
            font-size: 12px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .item span {
            color: #123b68;
            font-weight: bold;
            font-size: 14px;
        }

        .footer {
            text-align: center;
            color: #64748b;
            font-size: 12px;
            line-height: 1.7;
            border-top: 1px solid #e2e8f0;
            padding-top: 18px;
            margin-top: 28px;
        }

        @media (max-width: 550px) {
            body {
                padding: 12px;
            }

            .container {
                padding: 22px;
            }

            .details {
                grid-template-columns: 1fr;
            }

            .header h1 {
                font-size: 21px;
            }
        }

        .privacy-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e3a8a;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 12px;
            margin-bottom: 18px;
        }
        .reset-link { display: inline-block; margin-top: 18px; color: #123b68; font-size: 13px; font-weight: bold; text-decoration: none; }
        .reset-link:hover { text-decoration: underline; }
    </style>
</head>

<body>

<div class="container">

    <div class="header">
        <img
            src="{{ asset('assets/barangay_cawit_logo.png') }}"
            alt="Barangay Cawit Logo"
            class="logo"
        >

        <h1>BARANGAY CAWIT</h1>
        <p>Municipality of Manito, Province of Albay</p>
        <h2>Certificate Verification</h2>
        <p>Official Electronic Certificate Authentication</p>
    </div>

    <form method="GET" action="{{ route('verify') }}" class="search-box">
        <input
            type="text"
            name="code"
            value="{{ $code ?? '' }}"
            placeholder="Enter Certificate No. (e.g., BRG-CERT-...) or Token"
            required
        >
        <button type="submit">🔍 Verify</button>
    </form>

    @if ($certificate && $displayDetails)
        <div class="valid">
            ✓ VALID CERTIFICATE
            <small>This electronic certificate is authentic, cryptographically verified, and has not been revoked.</small>
        </div>

        <div class="privacy-banner">
            @if ($showFullDetails)
                <span>🛡️ <strong>Authorized View:</strong> Full holder details displayed for Barangay Staff / Certificate Owner.</span>
            @else
                <span>🔒 <strong>RA 10173 Privacy Mode:</strong> Personal identifiers are partially masked for public protection.</span>
            @endif
            <span>🔐 Seal: <code>{{ $displayDetails['hash_fingerprint'] }}</code></span>
        </div>

        <div class="details">
            <div class="item"><strong>Certificate Number</strong><span>{{ $displayDetails['certificate_no'] }}</span></div>
            <div class="item"><strong>Certificate Holder</strong><span>{{ $displayDetails['holder_name'] }}</span></div>
            <div class="item"><strong>Document Type</strong><span>{{ $displayDetails['document_name'] }}</span></div>
            <div class="item"><strong>Reference Number</strong><span>{{ $displayDetails['reference_no'] }}</span></div>
            <div class="item"><strong>Date Issued (PHT)</strong><span>{{ $displayDetails['issued_at'] }}</span></div>
            <div class="item"><strong>Valid Until (PHT)</strong><span>{{ $displayDetails['expires_at'] }}</span></div>
            <div class="item"><strong>Issuing Office</strong><span>Barangay Cawit, Manito, Albay</span></div>
            <div class="item"><strong>Verification Status</strong><span>Valid &amp; Integrity Verified</span></div>
        </div>
        <div style="text-align: center;"><a href="{{ route('verify') }}" class="reset-link">↺ Verify Another Certificate</a></div>
    @elseif ($revokedCertificate && $displayDetails)
        <div class="invalid">
            ✕ REVOKED CERTIFICATE
            <p>{{ $error }}</p>
        </div>
        <div class="details">
            <div class="item"><strong>Certificate Number</strong><span>{{ $displayDetails['certificate_no'] }}</span></div>
            <div class="item"><strong>Certificate Holder</strong><span>{{ $displayDetails['holder_name'] }}</span></div>
            <div class="item"><strong>Document Type</strong><span>{{ $displayDetails['document_name'] }}</span></div>
            <div class="item"><strong>Date Revoked (PHT)</strong><span>{{ $displayDetails['revoked_at'] }}</span></div>
            @if ($displayDetails['revocation_reason'])
                <div class="item" style="grid-column: 1 / -1;"><strong>Revocation Reason</strong><span>{{ $displayDetails['revocation_reason'] }}</span></div>
            @endif
        </div>
        <div style="text-align: center;"><a href="{{ route('verify') }}" class="reset-link">↺ Verify Another Certificate</a></div>
        @elseif ($expiredCertificate && $displayDetails)
        <div class="invalid" style="background: #fef3c7; border-color: #fcd34d; color: #92400e;">
            ⌛ EXPIRED CERTIFICATE
            <p>{{ $error }}</p>
        </div>
        <div class="details">
            <div class="item"><strong>Certificate Number</strong><span>{{ $displayDetails['certificate_no'] }}</span></div>
            <div class="item"><strong>Certificate Holder</strong><span>{{ $displayDetails['holder_name'] }}</span></div>
            <div class="item"><strong>Document Type</strong><span>{{ $displayDetails['document_name'] }}</span></div>
            <div class="item"><strong>Expired On (PHT)</strong><span>{{ $displayDetails['expires_at'] }}</span></div>
        </div>
        <div style="text-align: center;"><a href="{{ route('verify') }}" class="reset-link">↺ Verify Another Certificate</a></div>
    @elseif ($searched)
        <div class="invalid">
            ✕ INVALID CERTIFICATE
            <p>{{ $error }}</p>
        </div>
        <div style="text-align: center;"><a href="{{ route('verify') }}" class="reset-link">↺ Try Another Search</a></div>
    @else
        <div class="info-box">
            Enter a <strong>Certificate Number</strong> above or scan the <strong>QR Code</strong> on an official Barangay Cawit E-Certificate to verify its authenticity.
        </div>
    @endif

    <div class="footer">
        <strong>BARANGAY CAWIT</strong><br>
        Municipality of Manito, Province of Albay<br>
        Barangay Document Request and Verifiable E-Certificate System<br>
        <small>QR Code Authentication and Verification</small>
    </div>

</div>

</body>
</html>
