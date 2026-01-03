<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Summary - {{ $audit->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header .meta {
            margin-top: 10px;
            font-size: 10px;
            color: #666;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            font-size: 16px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .stat-box {
            display: inline-block;
            border: 1px solid #ddd;
            padding: 10px;
            margin: 5px;
            min-width: 150px;
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .stat-label {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        .kv {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .kv td {
            border: none;
            padding: 4px 0;
            vertical-align: top;
        }
        .kv td.key {
            width: 30%;
            color: #555;
            font-weight: bold;
            padding-right: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Audit Summary</h1>
        <div class="meta">
            <strong>Audit:</strong> {{ $audit->name }}<br>
            <strong>Export Date:</strong> {{ $exportDate->format('Y-m-d H:i:s') }}
        </div>
    </div>

    <div class="section">
        <h2>Audit Information</h2>
        <table class="kv">
            <tr>
                <td class="key">Name:</td>
                <td>{{ $audit->name }}</td>
            </tr>
            <tr>
                <td class="key">Status:</td>
                <td>{{ ucfirst($audit->status) }}</td>
            </tr>
            <tr>
                <td class="key">Type:</td>
                <td>{{ ucfirst($audit->audit_type ?? 'N/A') }}</td>
            </tr>
            <tr>
                <td class="key">Compliance Standards:</td>
                <td>{{ is_array($audit->compliance_standards) ? implode(', ', $audit->compliance_standards) : 'N/A' }}</td>
            </tr>
            @if($audit->start_date)
            <tr>
                <td class="key">Start Date:</td>
                <td>{{ $audit->start_date->format('Y-m-d') }}</td>
            </tr>
            @endif
            @if($audit->end_date)
            <tr>
                <td class="key">End Date:</td>
                <td>{{ $audit->end_date->format('Y-m-d') }}</td>
            </tr>
            @endif
            @if($audit->creator)
            <tr>
                <td class="key">Created By:</td>
                <td>{{ $audit->creator->name }}</td>
            </tr>
            @endif
            @if($audit->auditor)
            <tr>
                <td class="key">Auditor:</td>
                <td>{{ $audit->auditor->name }}</td>
            </tr>
            @endif
            @if($audit->scope)
            <tr>
                <td class="key">Scope:</td>
                <td>{{ $audit->scope }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="section">
        <h2>Evidence Statistics</h2>
        <div style="text-align: center;">
            <div class="stat-box">
                <div class="stat-value">{{ $statistics['total_evidences'] ?? 0 }}</div>
                <div class="stat-label">Total Evidences</div>
            </div>
            <div class="stat-box">
                <div class="stat-value" style="color: #22c55e;">{{ $statistics['approved_evidences'] ?? 0 }}</div>
                <div class="stat-label">Approved</div>
            </div>
            <div class="stat-box">
                <div class="stat-value" style="color: #f59e0b;">{{ $statistics['pending_evidences'] ?? 0 }}</div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-box">
                <div class="stat-value" style="color: #ef4444;">{{ $statistics['rejected_evidences'] ?? 0 }}</div>
                <div class="stat-label">Rejected</div>
            </div>
        </div>
    </div>

    @if($audit->description)
    <div class="section">
        <h2>Description</h2>
        <p>{{ $audit->description }}</p>
    </div>
    @endif

    <div class="footer" style="margin-top: 40px; padding-top: 10px; border-top: 1px solid #ccc; font-size: 10px; color: #666;">
        <p>Generated on {{ $exportDate->format('Y-m-d H:i:s') }} | AuditReady Platform</p>
    </div>
</body>
</html>
