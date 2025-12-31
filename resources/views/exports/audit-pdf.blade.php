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

        /* DomPDF-friendly wrapping + evidence blocks (avoid wide tables) */
        .break-all {
            word-break: break-all;
            overflow-wrap: anywhere;
        }
        .muted {
            color: #666;
        }
        .mono {
            font-family: monospace;
            font-size: 10px;
        }
        .evidence-card {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }
        .page-break {
            page-break-after: always;
        }
        .evidence-title {
            font-weight: bold;
            margin: 0 0 8px 0;
        }
        .kv {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .kv td {
            border: none;
            padding: 2px 0;
            vertical-align: top;
        }
        .kv td.key {
            width: 32%;
            color: #555;
            font-weight: bold;
            padding-right: 10px;
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
    @php
        /**
         * Render values in an audit-friendly way (DomPDF-safe).
         */
        $formatAuditValue = function ($value): string {
            if (is_null($value)) {
                return '—';
            }

            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }

            if (is_string($value)) {
                $trimmed = trim($value);
                // Attempt to decode JSON strings.
                if (($trimmed !== '') && (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '['))) {
                    $decoded = json_decode($trimmed, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $value = $decoded;
                    }
                }
            }

            if (is_array($value)) {
                // If it's a simple list, join.
                $isList = array_keys($value) === range(0, count($value) - 1);
                if ($isList) {
                    return implode(', ', array_map(fn ($v) => is_scalar($v) ? (string) $v : json_encode($v), $value));
                }

                // Fallback: pretty JSON for complex objects.
                return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }

            return (string) $value;
        };

        $decodeAuditPayload = function ($payload): array {
            if (is_null($payload)) {
                return [];
            }

            if (is_string($payload)) {
                $decoded = json_decode($payload, true);
                return (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : ['details' => $payload];
            }

            if (is_array($payload)) {
                return $payload;
            }

            return ['details' => (string) $payload];
        };
    @endphp

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
            @foreach($evidences as $evidence)
                <div class="evidence-card">
                    <p class="evidence-title break-all">
                        {{ $evidence->filename ?: ('Evidence #' . $evidence->id) }}
                        <span class="muted"> — v{{ $evidence->version }}</span>
                    </p>

                    <table class="kv">
                        <tbody>
                            <tr>
                                <td class="key">Category</td>
                                <td>{{ $evidence->category ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="key">Validation Status</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $evidence->validation_status ?? 'pending')) }}</td>
                            </tr>
                            <tr>
                                <td class="key">Document Date</td>
                                <td>{{ $evidence->document_date?->format('Y-m-d') ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="key">Regulatory Reference</td>
                                <td class="break-all">{{ $evidence->regulatory_reference ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="key">MIME Type</td>
                                <td>{{ $evidence->mime_type ?: 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="key">Size</td>
                                <td>{{ number_format($evidence->size ?? 0) }} bytes</td>
                            </tr>
                            <tr>
                                <td class="key">Uploader</td>
                                <td>{{ $evidence->uploader->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="key">Uploaded At</td>
                                <td>{{ $evidence->created_at?->format('Y-m-d H:i:s') ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td class="key">Checksum</td>
                                <td class="mono break-all">{{ $evidence->checksum ?: 'N/A' }}</td>
                            </tr>
                            @if(isset($evidenceLinks[$evidence->id]))
                            <tr>
                                <td class="key">Download</td>
                                <td>
                                    <a href="{{ $evidenceLinks[$evidence->id] }}" style="color: #3F7FB3; text-decoration: underline;">
                                        Download evidenza
                                    </a>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            @endforeach
        @else
        <p>No evidences found for this audit.</p>
        @endif
    </div>

    {{-- Page break after evidences to keep audit trail readable --}}
    <div class="page-break"></div>

    @if($auditLogs->count() > 0)
    <div class="section">
        <h2>Audit Trail ({{ $auditLogs->count() }} entries)</h2>
        @foreach($auditLogs as $log)
            <div class="evidence-card">
                <p class="evidence-title">
                    {{ ucfirst($log->action) }}
                    <span class="muted"> — {{ $log->created_at->format('Y-m-d H:i:s') }}</span>
                </p>

                <table class="kv">
                    <tbody>
                        <tr>
                            <td class="key">User</td>
                            <td>{{ $log->user->name ?? 'System' }}</td>
                        </tr>
                        <tr>
                            <td class="key">IP Address</td>
                            <td>{{ $log->ip_address ?? 'N/A' }}</td>
                        </tr>
                        @php
                            $payload = $decodeAuditPayload($log->payload);
                            $action = strtolower($log->action ?? '');

                            // Handle "Created" action: payload has "attributes" with all fields
                            if ($action === 'created' && is_array($payload) && isset($payload['attributes'])) {
                                $attributes = $payload['attributes'];
                                $keys = array_keys($attributes);
                            }
                            // Handle "Updated" action: payload has "old" and "new"
                            elseif (is_array($payload) && (isset($payload['old']) || isset($payload['new']))) {
                                $old = $payload['old'] ?? [];
                                $new = $payload['new'] ?? [];
                                $old = is_array($old) ? $old : [];
                                $new = is_array($new) ? $new : [];
                                $keys = array_values(array_unique(array_merge(array_keys($old), array_keys($new))));
                            }
                            // Fallback: try to extract keys from payload directly
                            elseif (is_array($payload) && !empty($payload)) {
                                $keys = array_keys($payload);
                            }
                            else {
                                $keys = [];
                            }
                        @endphp

                        @if ($action === 'created' && isset($attributes))
                            {{-- Display all attributes for "Created" action --}}
                            @foreach ($keys as $key)
                                @php
                                    $value = $attributes[$key] ?? null;
                                @endphp
                                <tr>
                                    <td class="key">{{ $key }}</td>
                                    <td class="mono break-all">{{ $formatAuditValue($value) }}</td>
                                </tr>
                            @endforeach
                        @elseif (isset($old) && isset($new))
                            {{-- Display diff for "Updated" action --}}
                            @foreach ($keys as $key)
                                @php
                                    $oldVal = $old[$key] ?? null;
                                    $newVal = $new[$key] ?? null;

                                    // Skip unchanged fields to reduce noise.
                                    if ($oldVal === $newVal) {
                                        continue;
                                    }
                                @endphp
                                <tr>
                                    <td class="key">{{ $key }}</td>
                                    <td class="mono break-all">
                                        <span class="muted">old:</span> {{ $formatAuditValue($oldVal) }}
                                        <br>
                                        <span class="muted">new:</span> {{ $formatAuditValue($newVal) }}
                                    </td>
                                </tr>
                            @endforeach
                        @elseif (! empty($payload))
                            <tr>
                                <td class="key">Details</td>
                                <td class="mono break-all">{{ $formatAuditValue($payload) }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>
    @endif

    <div class="footer">
        <p>This document was generated automatically by AuditReady on {{ $exportDate->format('Y-m-d H:i:s') }}</p>
        <p>This is a confidential document. Unauthorized distribution is prohibited.</p>
    </div>
</body>
</html>
