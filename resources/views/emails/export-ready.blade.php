<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Audit Export Ready</title>
</head>
<body>
    <h2>Audit Export Ready</h2>
    
    <p>Hello,</p>
    
    <p>Your export of the audit <strong>{{ $audit->name }}</strong> in <strong>{{ $format }}</strong> format is ready for download.</p>
    
    <p>
        <a href="{{ $downloadUrl }}" style="background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
            Download Export
        </a>
    </p>
    
    <p><strong>Important:</strong> This download link will expire on {{ $expiresAt->format('Y-m-d H:i:s') }} (24 hours from now).</p>
    
    <p>If you did not request this export, please ignore this email or contact your system administrator.</p>
    
    <hr>
    
    <p style="color: #666; font-size: 12px;">
        This is an automated message from AuditReady. Please do not reply to this email.
    </p>
</body>
</html>
