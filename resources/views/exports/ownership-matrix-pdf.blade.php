<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Ownership Matrix</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 15px;
        }
        .header {
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
        }
        .header .meta {
            margin-top: 8px;
            font-size: 10px;
            color: #666;
        }
        .statistics {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .statistics h3 {
            margin: 0 0 8px 0;
            font-size: 14px;
        }
        .statistics table {
            width: 100%;
            border-collapse: collapse;
        }
        .statistics td {
            padding: 4px 8px;
            border: none;
        }
        .statistics td.key {
            font-weight: bold;
            width: 50%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .control-card {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 8px;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }
        .control-title {
            font-weight: bold;
            margin: 0 0 6px 0;
            font-size: 12px;
        }
        .muted {
            color: #666;
            font-size: 9px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
            font-size: 9px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Control Ownership Matrix</h1>
        <div class="meta">
            <p><strong>Export Date:</strong> {{ $exportDate->format('Y-m-d H:i:s') }}</p>
            @if(isset($filters['standard']))
                <p><strong>Standard:</strong> {{ $filters['standard'] }}</p>
            @endif
            @if(isset($filters['category']))
                <p><strong>Category:</strong> {{ $filters['category'] }}</p>
            @endif
            @if(isset($filters['without_owners']) && $filters['without_owners'])
                <p><strong>Filter:</strong> Controls without owners</p>
            @endif
        </div>
    </div>

    @if(isset($statistics))
    <div class="statistics">
        <h3>Ownership Statistics</h3>
        <table>
            <tr>
                <td class="key">Total Controls</td>
                <td>{{ $statistics['total_controls'] }}</td>
            </tr>
            <tr>
                <td class="key">Controls with Owners</td>
                <td>{{ $statistics['controls_with_owners'] }}</td>
            </tr>
            <tr>
                <td class="key">Controls without Owners</td>
                <td>{{ $statistics['controls_without_owners'] }}</td>
            </tr>
            <tr>
                <td class="key">Coverage Percentage</td>
                <td>{{ $statistics['coverage_percentage'] }}%</td>
            </tr>
            <tr>
                <td class="key">Total Assignments</td>
                <td>{{ $statistics['total_assignments'] }}</td>
            </tr>
        </table>
    </div>
    @endif

    <div>
        <h2 style="font-size: 14px; margin-bottom: 10px;">Controls and Owners</h2>
        @if(count($matrix) > 0)
            @foreach($matrix as $control)
                <div class="control-card">
                    <p class="control-title">
                        {{ $control['article_reference'] ?? 'N/A' }}: {{ $control['title'] }}
                        <span class="muted"> — {{ $control['standard'] }}</span>
                    </p>
                    @if($control['category'])
                        <p class="muted" style="margin: 4px 0;">Category: {{ $control['category'] }}</p>
                    @endif
                    @if(count($control['owners']) > 0)
                        <table>
                            <thead>
                                <tr>
                                    <th>Owner Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Responsibility</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($control['owners'] as $owner)
                                <tr>
                                    <td>{{ $owner['user_name'] }}</td>
                                    <td>{{ $owner['user_email'] }}</td>
                                    <td>{{ $owner['role_name'] ?? 'N/A' }}</td>
                                    <td>{{ ucfirst($owner['responsibility_level']) }}</td>
                                    <td>{{ $owner['notes'] ?? '' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="muted" style="margin: 4px 0;">No owners assigned</p>
                    @endif
                </div>
            @endforeach
        @else
            <p>No controls found matching the selected filters.</p>
        @endif
    </div>

    <div class="footer">
        <p>This document was generated automatically by AuditReady on {{ $exportDate->format('Y-m-d H:i:s') }}</p>
        <p>This is a confidential document. Unauthorized distribution is prohibited.</p>
    </div>
</body>
</html>
