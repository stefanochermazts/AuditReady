<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Day Pack - {{ $audit->name }}</title>
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
            page-break-inside: avoid;
        }
        .section h2 {
            font-size: 16px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .page-break {
            page-break-after: always;
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
        .evidence-item {
            margin: 4px 0;
            padding: 4px;
            background-color: #f9f9f9;
            border-left: 3px solid #ccc;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Audit Day Pack</h1>
        <div class="meta">
            <strong>Audit:</strong> {{ $audit->name }}<br>
            <strong>Export Date:</strong> {{ $exportDate->format('Y-m-d H:i:s') }}
        </div>
    </div>

    <div class="section page-break">
        <h2>Audit Summary</h2>
        <table>
            <tr>
                <td style="width: 30%; font-weight: bold;">Name:</td>
                <td>{{ $audit->name }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Status:</td>
                <td>{{ ucfirst($audit->status) }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Type:</td>
                <td>{{ ucfirst($audit->audit_type ?? 'N/A') }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Compliance Standards:</td>
                <td>{{ is_array($audit->compliance_standards) ? implode(', ', $audit->compliance_standards) : 'N/A' }}</td>
            </tr>
            @if($audit->start_date)
            <tr>
                <td style="font-weight: bold;">Start Date:</td>
                <td>{{ $audit->start_date->format('Y-m-d') }}</td>
            </tr>
            @endif
            @if($audit->end_date)
            <tr>
                <td style="font-weight: bold;">End Date:</td>
                <td>{{ $audit->end_date->format('Y-m-d') }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="section page-break">
        <h2>Controls and Evidences</h2>
        @forelse($controls as $control)
            <div style="margin-bottom: 20px; page-break-inside: avoid;">
                <h3 style="font-size: 14px; margin-bottom: 8px;">
                    {{ $control->article_reference ?? 'N/A' }} - {{ $control->title }}
                </h3>
                <p style="font-size: 11px; color: #666; margin-bottom: 8px;">
                    <strong>Standard:</strong> {{ $control->standard }} | 
                    <strong>Category:</strong> {{ $control->category ?? 'N/A' }}
                </p>
                @if(isset($evidencesByControl[$control->id]) && count($evidencesByControl[$control->id]) > 0)
                    <div style="margin-left: 20px;">
                        <strong>Evidences ({{ count($evidencesByControl[$control->id]) }}):</strong>
                        @foreach($evidencesByControl[$control->id] as $evidence)
                            <div class="evidence-item">
                                • {{ $evidence->filename }} (v{{ $evidence->version }})
                                @if($evidence->validation_status)
                                    [{{ ucfirst($evidence->validation_status) }}]
                                @endif
                                @if($evidence->document_date)
                                    - {{ $evidence->document_date->format('Y-m-d') }}
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p style="color: #999; margin-left: 20px;">No evidences linked</p>
                @endif
            </div>
        @empty
            <p style="color: #999;">No controls found</p>
        @endforelse
    </div>

    @if($auditLogs->isNotEmpty())
    <div class="section page-break">
        <h2>Audit Trail</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 20%;">Timestamp</th>
                    <th style="width: 15%;">Action</th>
                    <th style="width: 20%;">User</th>
                    <th style="width: 45%;">Details</th>
                </tr>
            </thead>
            <tbody>
                @foreach($auditLogs as $log)
                    <tr>
                        <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                        <td>{{ ucfirst($log->action) }}</td>
                        <td>{{ $log->user_id ? ($log->user->name ?? 'User #' . $log->user_id) : 'System' }}</td>
                        <td style="font-size: 10px;">
                            @if($log->payload)
                                @php
                                    $details = is_string($log->payload) ? json_decode($log->payload, true) : $log->payload;
                                    if (is_array($details)) {
                                        echo json_encode($details, JSON_PRETTY_PRINT);
                                    } else {
                                        echo $log->payload;
                                    }
                                @endphp
                            @else
                                <span style="color: #999;">No details</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer" style="margin-top: 40px; padding-top: 10px; border-top: 1px solid #ccc; font-size: 10px; color: #666;">
        <p>Generated on {{ $exportDate->format('Y-m-d H:i:s') }} | AuditReady Platform</p>
    </div>
</body>
</html>
