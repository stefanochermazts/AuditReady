<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Index - {{ $audit->name }}</title>
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
        .control-row {
            page-break-inside: avoid;
        }
        .evidence-list {
            margin-left: 20px;
            font-size: 11px;
            color: #555;
        }
        .evidence-item {
            margin: 4px 0;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Control Index</h1>
        <div class="meta">
            <strong>Audit:</strong> {{ $audit->name }}<br>
            <strong>Export Date:</strong> {{ $exportDate->format('Y-m-d H:i:s') }}
        </div>
    </div>

    <div class="section">
        <h2>Controls and Linked Evidences</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 15%;">Standard</th>
                    <th style="width: 15%;">Article Reference</th>
                    <th style="width: 30%;">Title</th>
                    <th style="width: 15%;">Category</th>
                    <th style="width: 25%;">Evidences</th>
                </tr>
            </thead>
            <tbody>
                @forelse($controls as $control)
                    <tr class="control-row">
                        <td>{{ $control->standard }}</td>
                        <td>{{ $control->article_reference ?? 'N/A' }}</td>
                        <td>{{ $control->title }}</td>
                        <td>{{ $control->category ?? 'N/A' }}</td>
                        <td>
                            @if(isset($evidencesByControl[$control->id]) && count($evidencesByControl[$control->id]) > 0)
                                <div class="evidence-list">
                                    @foreach($evidencesByControl[$control->id] as $evidence)
                                        <div class="evidence-item">
                                            • {{ $evidence->filename }} (v{{ $evidence->version }})
                                            @if($evidence->validation_status)
                                                [{{ ucfirst($evidence->validation_status) }}]
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span style="color: #999;">No evidences</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: #999;">No controls found</td>
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
