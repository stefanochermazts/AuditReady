<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trail - {{ $audit->name }}</title>
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
        .log-entry {
            page-break-inside: avoid;
        }
        .details {
            font-size: 10px;
            color: #666;
            font-family: monospace;
            white-space: pre-wrap;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Audit Trail</h1>
        <div class="meta">
            <strong>Audit:</strong> {{ $audit->name }}<br>
            <strong>Export Date:</strong> {{ $exportDate->format('Y-m-d H:i:s') }}<br>
            <strong>Total Entries:</strong> {{ $auditLogs->count() }}
        </div>
    </div>

    <div class="section">
        <h2>Activity Log</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 15%;">Timestamp</th>
                    <th style="width: 15%;">Action</th>
                    <th style="width: 15%;">User</th>
                    <th style="width: 15%;">IP Address</th>
                    <th style="width: 40%;">Details</th>
                </tr>
            </thead>
            <tbody>
                @forelse($auditLogs as $log)
                    <tr class="log-entry">
                        <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                        <td>{{ ucfirst($log->action) }}</td>
                        <td>{{ $log->user_id ? ($log->user->name ?? 'User #' . $log->user_id) : 'System' }}</td>
                        <td>{{ $log->ip_address ?? 'N/A' }}</td>
                        <td>
                            @if($log->payload)
                                <div class="details">
                                    @php
                                        $details = is_string($log->payload) ? json_decode($log->payload, true) : $log->payload;
                                        if (is_array($details)) {
                                            // Format array as readable text
                                            $formatted = [];
                                            foreach ($details as $key => $value) {
                                                if (is_array($value)) {
                                                    $formatted[] = $key . ': ' . json_encode($value, JSON_PRETTY_PRINT);
                                                } else {
                                                    $formatted[] = $key . ': ' . $value;
                                                }
                                            }
                                            echo implode("\n", $formatted);
                                        } else {
                                            echo $log->payload;
                                        }
                                    @endphp
                                </div>
                            @else
                                <span style="color: #999;">No details</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: #999;">No audit log entries found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer" style="margin-top: 40px; padding-top: 10px; border-top: 1px solid #ccc; font-size: 10px; color: #666;">
        <p>Generated on {{ $exportDate->format('Y-m-d H:i:s') }} | AuditReady Platform</p>
    </div>
</body>
</html>
