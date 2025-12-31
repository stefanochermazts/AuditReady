<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Export - {{ $audit->name }}</title>
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
        .footer {
            margin-top: 40px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Audit Export: {{ $audit->name }}</h1>
        <div class="meta">
            <p><strong>Export Date:</strong> {{ $exportDate->format('Y-m-d H:i:s') }}</p>
            <p><strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $audit->status)) }}</p>
            <p><strong>Audit Type:</strong> {{ ucfirst($audit->audit_type ?? 'N/A') }}</p>
            @if($audit->compliance_standards && count($audit->compliance_standards) > 0)
                <p><strong>Compliance Standards:</strong> {{ implode(', ', $audit->compliance_standards) }}</p>
            @endif
            @if($audit->start_date)
                <p><strong>Start Date:</strong> {{ $audit->start_date->format('Y-m-d') }}</p>
            @endif
            @if($audit->end_date)
                <p><strong>End Date:</strong> {{ $audit->end_date->format('Y-m-d') }}</p>
            @endif
            @if($audit->reference_period_start)
                <p><strong>Reference Period:</strong> {{ $audit->reference_period_start->format('Y-m-d') }} - {{ $audit->reference_period_end?->format('Y-m-d') ?? 'N/A' }}</p>
            @endif
            @if($audit->auditor)
                <p><strong>Auditor:</strong> {{ $audit->auditor->name }}</p>
            @endif
            @if($audit->creator)
                <p><strong>Created By:</strong> {{ $audit->creator->name }}</p>
            @endif
        </div>
    </div>

    @if($audit->description)
    <div class="section">
        <h2>Description</h2>
        <p>{{ $audit->description }}</p>
    </div>
    @endif

    @if($audit->scope)
    <div class="section">
        <h2>Scope</h2>
        <p>{{ $audit->scope }}</p>
    </div>
    @endif

    @if($audit->objectives)
    <div class="section">
        <h2>Objectives</h2>
        <p>{{ $audit->objectives }}</p>
    </div>
    @endif

    @if($audit->gdpr_article_reference || $audit->dora_requirement_reference || $audit->nis2_requirement_reference)
    <div class="section">
        <h2>Compliance References</h2>
        @if($audit->gdpr_article_reference)
            <p><strong>GDPR:</strong> {{ $audit->gdpr_article_reference }}</p>
        @endif
        @if($audit->dora_requirement_reference)
            <p><strong>DORA:</strong> {{ $audit->dora_requirement_reference }}</p>
        @endif
        @if($audit->nis2_requirement_reference)
            <p><strong>NIS2:</strong> {{ $audit->nis2_requirement_reference }}</p>
        @endif
    </div>
    @endif

    <div class="section">
        <h2>Evidences ({{ $evidences->count() }})</h2>
        @if($evidences->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Filename</th>
                    <th>Category</th>
                    <th>Document Date</th>
                    <th>Validation Status</th>
                    <th>Regulatory Reference</th>
                    <th>MIME Type</th>
                    <th>Size</th>
                    <th>Version</th>
                    <th>Uploader</th>
                    <th>Uploaded At</th>
                    <th>Checksum</th>
                </tr>
            </thead>
            <tbody>
                @foreach($evidences as $evidence)
                <tr>
                    <td>{{ $evidence->filename }}</td>
                    <td>{{ $evidence->category ?? 'N/A' }}</td>
                    <td>{{ $evidence->document_date?->format('Y-m-d') ?? 'N/A' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $evidence->validation_status ?? 'pending')) }}</td>
                    <td>{{ $evidence->regulatory_reference ?? 'N/A' }}</td>
                    <td>{{ $evidence->mime_type }}</td>
                    <td>{{ number_format($evidence->size) }} bytes</td>
                    <td>{{ $evidence->version }}</td>
                    <td>{{ $evidence->uploader->name ?? 'N/A' }}</td>
                    <td>{{ $evidence->created_at->format('Y-m-d H:i:s') }}</td>
                    <td style="font-family: monospace; font-size: 10px;">{{ substr($evidence->checksum, 0, 16) }}...</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>No evidences found for this audit.</p>
        @endif
    </div>

    @if($auditLogs->count() > 0)
    <div class="section">
        <h2>Audit Trail ({{ $auditLogs->count() }} entries)</h2>
        <table>
            <thead>
                <tr>
                    <th>Date/Time</th>
                    <th>Action</th>
                    <th>User</th>
                    <th>IP Address</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                @foreach($auditLogs as $log)
                <tr>
                    <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                    <td>{{ ucfirst($log->action) }}</td>
                    <td>{{ $log->user->name ?? 'System' }}</td>
                    <td>{{ $log->ip_address ?? 'N/A' }}</td>
                    <td>
                        @if($log->payload)
                            {{ json_encode($log->payload, JSON_PRETTY_PRINT) }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>This document was generated automatically by AuditReady on {{ $exportDate->format('Y-m-d H:i:s') }}</p>
        <p>This is a confidential document. Unauthorized distribution is prohibited.</p>
    </div>
</body>
</html>
