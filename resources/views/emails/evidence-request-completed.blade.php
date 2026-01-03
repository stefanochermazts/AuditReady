<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evidence Upload Completed</title>
    <style>
        /* Enterprise Audit Design System Colors */
        :root {
            --primary-500: #3F7FB3;
            --primary-600: #356A97;
            --primary-700: #2D587C;
            --success-500: #12B76A;
            --success-600: #039855;
            --warning-500: #F79009;
            --danger-500: #F04438;
            --neutral-50: #F9FAFB;
            --neutral-100: #F2F4F7;
            --neutral-200: #EAECF0;
            --neutral-500: #667085;
            --neutral-600: #475467;
            --neutral-700: #344054;
            --neutral-800: #1D2939;
            --neutral-900: #101828;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: var(--neutral-800);
            background-color: var(--neutral-50);
            margin: 0;
            padding: 0;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }

        .email-header {
            background-color: var(--success-600);
            padding: 24px;
            text-align: center;
        }

        .email-header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }

        .email-body {
            padding: 32px 24px;
        }

        .greeting {
            font-size: 16px;
            color: var(--neutral-800);
            margin-bottom: 24px;
        }

        .message {
            font-size: 15px;
            color: var(--neutral-700);
            margin-bottom: 24px;
            line-height: 1.7;
        }

        .success-box {
            background-color: #ECFDF3;
            border-left: 4px solid var(--success-600);
            padding: 16px;
            margin: 24px 0;
            border-radius: 4px;
        }

        .success-box h3 {
            margin: 0 0 8px 0;
            font-size: 14px;
            font-weight: 600;
            color: var(--success-700);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .success-box p {
            margin: 4px 0;
            font-size: 15px;
            color: var(--neutral-800);
        }

        .control-info {
            background-color: var(--neutral-50);
            border-left: 4px solid var(--primary-500);
            padding: 16px;
            margin: 24px 0;
            border-radius: 4px;
        }

        .control-info h3 {
            margin: 0 0 8px 0;
            font-size: 14px;
            font-weight: 600;
            color: var(--neutral-600);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .control-info p {
            margin: 4px 0;
            font-size: 15px;
            color: var(--neutral-800);
        }

        .control-info .reference {
            font-weight: 600;
            color: var(--primary-600);
        }

        .cta-button {
            display: inline-block;
            background-color: var(--primary-500);
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 15px;
            margin: 24px 0;
            text-align: center;
            transition: background-color 0.2s;
        }

        .cta-button:hover {
            background-color: var(--primary-600);
        }

        .cta-container {
            text-align: center;
            margin: 32px 0;
        }

        .details {
            background-color: var(--neutral-50);
            border: 1px solid var(--neutral-200);
            border-radius: 4px;
            padding: 16px;
            margin: 24px 0;
        }

        .details-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--neutral-200);
        }

        .details-row:last-child {
            border-bottom: none;
        }

        .details-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--neutral-600);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .details-value {
            font-size: 14px;
            color: var(--neutral-800);
            text-align: right;
        }

        .email-footer {
            background-color: var(--neutral-100);
            padding: 24px;
            text-align: center;
            border-top: 1px solid var(--neutral-200);
        }

        .email-footer p {
            margin: 8px 0;
            font-size: 12px;
            color: var(--neutral-600);
        }

        .email-footer a {
            color: var(--primary-600);
            text-decoration: none;
        }

        @media only screen and (max-width: 600px) {
            .email-body {
                padding: 24px 16px;
            }

            .details-row {
                flex-direction: column;
            }

            .details-value {
                text-align: left;
                margin-top: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <h1>Evidence Upload Completed</h1>
        </div>

        <!-- Body -->
        <div class="email-body">
            <div class="greeting">
                Hello {{ $request->requestedBy->name }},
            </div>

            <div class="message">
                The supplier has successfully uploaded evidence files for the request you created. The files are now available in your AuditReady backoffice.
            </div>

            <!-- Success Box -->
            <div class="success-box">
                <h3>✓ Upload Completed</h3>
                <p>
                    <strong>{{ $filesCount }}</strong> file{{ $filesCount > 1 ? 's have' : ' has' }} been uploaded and is now available in your audit.
                </p>
            </div>

            <!-- Control Information -->
            <div class="control-info">
                <h3>Control Information</h3>
                <p>
                    <span class="reference">{{ $request->control->article_reference ?? 'Custom Control' }}:</span>
                    {{ $request->control->title }}
                </p>
            </div>

            <!-- CTA Button -->
            <div class="cta-container">
                <a href="{{ url('/admin/evidence-requests/' . $request->id) }}" class="cta-button">
                    View in Backoffice
                </a>
            </div>

            <!-- Details -->
            <div class="details">
                <div class="details-row">
                    <span class="details-label">Supplier</span>
                    <span class="details-value">{{ $request->supplier->name }}</span>
                </div>
                <div class="details-row">
                    <span class="details-label">Uploaded At</span>
                    <span class="details-value">{{ $request->completed_at->format('F j, Y \a\t g:i A') }}</span>
                </div>
                @if($request->audit)
                    <div class="details-row">
                        <span class="details-label">Related Audit</span>
                        <span class="details-value">{{ $request->audit->name }}</span>
                    </div>
                @endif
            </div>

            <div class="message" style="margin-top: 24px; font-size: 14px; color: var(--neutral-600);">
                The uploaded evidence has been automatically linked to the control and is ready for review in your audit management system.
            </div>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p>
                This is an automated notification from <strong>AuditReady</strong>.
            </p>
            <p>
                <a href="{{ url('/admin/evidence-requests') }}">View Evidence Requests</a>
            </p>
        </div>
    </div>
</body>
</html>
