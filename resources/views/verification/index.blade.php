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

    @if ($certificate)
        <div class="valid">
            ✓ VALID CERTIFICATE
            <small>This electronic certificate is authentic and has not been revoked.</small>
        </div>

        <div class="details">
            <div class="item"><strong>Certificate Number</strong><span>{{ $certificate->certificate_no }}</span></div>
            <div class="item"><strong>Resident Name</strong><span>{{ $certificate->request->user->full_name }}</span></div>
            <div class="item"><strong>Document Type</strong><span>{{ $certificate->request->documentType->document_name }}</span></div>
            <div class="item"><strong>Reference Number</strong><span>{{ $certificate->request->reference_no }}</span></div>
            <div class="item"><strong>Date Issued</strong><span>{{ \Carbon\Carbon::parse($certificate->issued_at)->format('F d, Y h:i A') }}</span></div>
            <div class="item"><strong>Verification Status</strong><span>Valid and Not Revoked</span></div>
        </div>
    @else
        <div class="invalid">
            ✕ INVALID CERTIFICATE
            <p>{{ $error }}</p>
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
