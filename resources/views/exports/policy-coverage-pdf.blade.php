<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Policy Coverage Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 20px; margin: 0 0 6px 0; }
        h2 { font-size: 16px; margin: 18px 0 8px 0; }
        .muted { color: #6b7280; }
        .meta { margin-bottom: 16px; }
        .kv { width: 100%; border-collapse: collapse; }
        .kv td { padding: 6px 8px; border: 1px solid #e5e7eb; }
        .kv td.key { width: 220px; background: #f9fafb; font-weight: 600; }
        .card { border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px; margin: 10px 0; }
        .pill { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; background: #f3f4f6; color: #374151; }
        .table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .table th, .table td { padding: 6px 8px; border: 1px solid #e5e7eb; vertical-align: top; }
        .table th { background: #f9fafb; text-align: left; font-weight: 700; }
        .notes { font-size: 10px; color: #4b5563; margin-top: 2px; }
    </style>
</head>
<body>
    <h1>Policy Coverage Report</h1>
    <div class="meta muted">
        Generated at: {{ $exportDate->format('Y-m-d H:i') }}
        @if(!empty($filters))
            <span> • Filters: {{ json_encode($filters) }}</span>
        @endif
    </div>

    @if(isset($coverage['statistics']))
        <h2>Summary</h2>
        <table class="kv">
            <tbody>
            <tr>
                <td class="key">Total Controls</td>
                <td>{{ $coverage['statistics']['total_controls'] ?? 0 }}</td>
            </tr>
            <tr>
                <td class="key">Mapped Controls</td>
                <td>{{ $coverage['statistics']['mapped_controls'] ?? 0 }}</td>
            </tr>
            <tr>
                <td class="key">Coverage Percentage</td>
                <td><strong>{{ $coverage['statistics']['coverage_percentage'] ?? 0 }}%</strong></td>
            </tr>
            <tr>
                <td class="key">Total Policies</td>
                <td>{{ $coverage['statistics']['total_policies'] ?? 0 }}</td>
            </tr>
            </tbody>
        </table>
    @endif

    <h2>Details</h2>
    <p class="muted">
        This report shows which policies cover which controls.
    </p>

    @foreach($coverage['report'] ?? [] as $item)
        @php
            $control = $item['control'];
            $policies = $item['policies'] ?? collect();
            $mappings = $item['mappings'] ?? collect();
        @endphp

        @if(($item['policy_count'] ?? 0) > 0)
            <div class="card">
                <div style="margin-bottom: 6px;">
                    <span class="pill">{{ $control->standard }}</span>
                    <strong style="margin-left: 6px;">
                        {{ $control->article_reference ?? 'N/A' }} - {{ $control->title }}
                    </strong>
                </div>

                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 45%;">Policy</th>
                            <th style="width: 30%;">Mapped By</th>
                            <th style="width: 25%;">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($policies as $policy)
                            @php
                                $mapping = $mappings->firstWhere('policy.id', $policy->id);
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $policy->name }}</strong> (v{{ $policy->version }})
                                    @if($policy->internal_link)
                                        <div class="notes">Link: {{ $policy->internal_link }}</div>
                                    @endif
                                </td>
                                <td>
                                    {{ $mapping['mapped_by']?->name ?? 'N/A' }}
                                    <div class="notes">
                                        {{ optional($mapping['mapped_at'])->format('Y-m-d H:i') ?? '' }}
                                    </div>
                                </td>
                                <td>
                                    {{ $mapping['coverage_notes'] ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endforeach
</body>
</html>

