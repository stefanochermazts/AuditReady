<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gap Snapshot Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
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
            font-size: 20px;
        }
        .header .meta {
            margin-top: 8px;
            font-size: 9px;
            color: #666;
        }
        .section {
            margin-bottom: 20px;
        }
        .section h2 {
            font-size: 14px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .statistics {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
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
            width: 60%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
        }
        table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .gap-item {
            margin-bottom: 10px;
            padding: 8px;
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        .high-risk-item {
            margin-bottom: 10px;
            padding: 8px;
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .gap-item h4, .high-risk-item h4 {
            margin: 0 0 4px 0;
            font-size: 11px;
            font-weight: bold;
        }
        .gap-item p, .high-risk-item p {
            margin: 2px 0;
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
        .page-break {
            page-break-after: always;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-yes {
            background-color: #d1fadf;
            color: #027a48;
        }
        .badge-no {
            background-color: #f8d7da;
            color: #842029;
        }
        .badge-partial {
            background-color: #fff3cd;
            color: #856404;
        }
        .badge-na {
            background-color: #e2e3e5;
            color: #41464b;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Gap Snapshot Report</h1>
        <div class="meta">
            <p><strong>Snapshot:</strong> {{ $snapshot->name }}</p>
            <p><strong>Standard:</strong> {{ $snapshot->standard }}</p>
            @if($snapshot->audit)
                <p><strong>Linked Audit:</strong> {{ $snapshot->audit->name }}</p>
            @endif
            @if($snapshot->completedBy)
                <p><strong>Completed By:</strong> {{ $snapshot->completedBy->name }}</p>
            @endif
            @if($snapshot->completed_at)
                <p><strong>Completed At:</strong> {{ $snapshot->completed_at->format('Y-m-d H:i:s') }}</p>
            @endif
            <p><strong>Export Date:</strong> {{ $exportDate->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="section">
        <div class="statistics">
            <h3>Summary Statistics</h3>
            <table>
                <tr>
                    <td class="key">Total Controls</td>
                    <td>{{ $statistics['total_controls'] }}</td>
                </tr>
                <tr>
                    <td class="key">Answered Controls</td>
                    <td>{{ $statistics['answered_controls'] }}</td>
                </tr>
                <tr>
                    <td class="key">Unanswered Controls</td>
                    <td>{{ $statistics['unanswered_controls'] }}</td>
                </tr>
                <tr>
                    <td class="key">Completion Percentage</td>
                    <td>{{ $statistics['completion_percentage'] }}%</td>
                </tr>
                <tr>
                    <td class="key">Yes Responses</td>
                    <td>{{ $statistics['yes_count'] }}</td>
                </tr>
                <tr>
                    <td class="key">No Responses</td>
                    <td>{{ $statistics['no_count'] }}</td>
                </tr>
                <tr>
                    <td class="key">Partial Responses</td>
                    <td>{{ $statistics['partial_count'] }}</td>
                </tr>
                <tr>
                    <td class="key">Not Applicable</td>
                    <td>{{ $statistics['not_applicable_count'] }}</td>
                </tr>
                <tr>
                    <td class="key">Gaps Identified</td>
                    <td>{{ $statistics['gaps_count'] }}</td>
                </tr>
                <tr>
                    <td class="key">Controls without Evidence</td>
                    <td>{{ $statistics['controls_without_evidence'] }}</td>
                </tr>
                <tr>
                    <td class="key">High Risk Controls</td>
                    <td>{{ $statistics['high_risk_controls'] }}</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Gaps by Category -->
    @if(count($gapAnalysis['gaps_by_category']) > 0)
    <div class="section">
        <h2>Gaps by Category</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 60%;">Category</th>
                    <th style="width: 40%;">Number of Gaps</th>
                </tr>
            </thead>
            <tbody>
                @foreach($gapAnalysis['gaps_by_category'] as $category => $count)
                    <tr>
                        <td>{{ $category }}</td>
                        <td>{{ $count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Identified Gaps -->
    @if(count($gapAnalysis['gaps']) > 0)
    <div class="page-break"></div>
    <div class="section">
        <h2>Identified Gaps ({{ count($gapAnalysis['gaps']) }} controls)</h2>
        <p class="text-muted" style="font-size: 9px; color: #666; margin-bottom: 10px;">
            Controls with "No" or "Partial" responses that may require attention.
        </p>
        @foreach($gapAnalysis['gaps'] as $gap)
            <div class="gap-item">
                <h4>{{ $gap['control_reference'] ?? 'Custom Control' }}: {{ $gap['control_title'] }}</h4>
                <p><strong>Category:</strong> {{ $gap['category'] ?? 'Uncategorized' }}</p>
                <p><strong>Response:</strong> 
                    <span class="badge badge-{{ $gap['response'] }}">
                        {{ ucfirst($gap['response']) }}
                    </span>
                </p>
                @if($gap['notes'])
                    <p><strong>Notes:</strong> {{ $gap['notes'] }}</p>
                @endif
                <p><strong>Has Evidence:</strong> {{ $gap['has_evidence'] ? 'Yes' : 'No' }}</p>
            </div>
        @endforeach
    </div>
    @endif

    <!-- Controls without Evidence -->
    @if(count($gapAnalysis['controls_without_evidence_list']) > 0)
    <div class="page-break"></div>
    <div class="section">
        <h2>Controls without Evidence ({{ count($gapAnalysis['controls_without_evidence_list']) }} controls)</h2>
        <p class="text-muted" style="font-size: 9px; color: #666; margin-bottom: 10px;">
            Controls that do not have evidence linked. Consider linking evidence to support your responses.
        </p>
        <table>
            <thead>
                <tr>
                    <th style="width: 20%;">Reference</th>
                    <th style="width: 40%;">Title</th>
                    <th style="width: 20%;">Category</th>
                    <th style="width: 20%;">Response</th>
                </tr>
            </thead>
            <tbody>
                @foreach($gapAnalysis['controls_without_evidence_list'] as $control)
                    <tr>
                        <td>{{ $control['control_reference'] ?? 'N/A' }}</td>
                        <td>{{ $control['control_title'] }}</td>
                        <td>{{ $control['category'] ?? 'Uncategorized' }}</td>
                        <td>
                            <span class="badge badge-{{ $control['response'] }}">
                                {{ ucfirst($control['response']) }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- High Risk Controls -->
    @if(count($gapAnalysis['high_risk_controls_list']) > 0)
    <div class="page-break"></div>
    <div class="section">
        <h2>High Risk Controls ({{ count($gapAnalysis['high_risk_controls_list']) }} controls)</h2>
        <p class="text-muted" style="font-size: 9px; color: #666; margin-bottom: 10px;">
            Controls with gaps (No/Partial response) AND no evidence linked. These may require immediate attention.
        </p>
        @foreach($gapAnalysis['high_risk_controls_list'] as $risk)
            <div class="high-risk-item">
                <h4>{{ $risk['control_reference'] ?? 'Custom Control' }}: {{ $risk['control_title'] }}</h4>
                <p><strong>Category:</strong> {{ $risk['category'] ?? 'Uncategorized' }}</p>
                <p><strong>Response:</strong> 
                    <span class="badge badge-{{ $risk['response'] }}">
                        {{ ucfirst($risk['response']) }}
                    </span>
                </p>
                @if($risk['notes'])
                    <p><strong>Notes:</strong> {{ $risk['notes'] }}</p>
                @endif
            </div>
        @endforeach
    </div>
    @endif

    <!-- Disclaimer -->
    <div class="page-break"></div>
    <div class="section">
        <h2>Important Notes</h2>
        <div style="background-color: #f9f9f9; padding: 10px; border-left: 4px solid #3F7FB3; font-size: 9px;">
            <p><strong>This report does NOT:</strong></p>
            <ul style="margin: 5px 0; padding-left: 20px;">
                <li>Assign scores or ratings</li>
                <li>Declare compliance or non-compliance</li>
                <li>Provide legal or regulatory advice</li>
                <li>Make recommendations</li>
            </ul>
            <p style="margin-top: 10px;">
                <strong>This report provides:</strong> A snapshot of control responses and identified gaps for informational purposes only. 
                It is intended to support internal assessment and audit preparation activities.
            </p>
        </div>
    </div>

    <div class="footer">
        <p>This document was generated automatically by AuditReady on {{ $exportDate->format('Y-m-d H:i:s') }}</p>
        <p>This is a confidential document. Unauthorized distribution is prohibited.</p>
    </div>
</body>
</html>
