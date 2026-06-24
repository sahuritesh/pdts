@php
    $severityDefs = config('delay_ews.severity', []);
    $alertDefs = config('delay_ews.alert_levels', []);
    $escalationDefs = config('delay_ews.escalation_levels', []);
@endphp
<div class="delay-ews-legend card border mb-3">
    <div class="card-body py-2 px-3">
        <a class="delay-ews-legend-toggle d-flex align-items-center justify-content-between text-decoration-none text-body"
            data-bs-toggle="collapse" href="#delayEwsLegendCollapse" role="button" aria-expanded="false"
            aria-controls="delayEwsLegendCollapse">
            <span class="small fw-semibold">
                <i class="ri-information-line me-1"></i> Delay severity, alert &amp; escalation guide
            </span>
            <i class="ri-arrow-down-s-line delay-ews-legend-chevron"></i>
        </a>
        <div class="collapse mt-2" id="delayEwsLegendCollapse">
            <p class="small text-muted mb-2">
                These values are <strong>auto-calculated</strong> when a delay is saved (from delay days and licensing impact).
            </p>
            <div class="row g-2 small">
                <div class="col-md-4">
                    <h6 class="delay-ews-legend-heading">Severity</h6>
                    <ul class="list-unstyled mb-0 delay-ews-legend-list">
                        @foreach($severityDefs as $code => $def)
                        <li class="mb-1">
                            <strong>{{ $def['label'] ?? ucfirst($code) }}</strong>
                            <span class="text-muted d-block">{{ $def['description'] ?? '' }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6 class="delay-ews-legend-heading">Alert level</h6>
                    <ul class="list-unstyled mb-0 delay-ews-legend-list">
                        @foreach($alertDefs as $code => $def)
                        <li class="mb-1">
                            <span class="badge bg-{{ $def['badge_class'] ?? 'secondary' }}">{{ $def['label'] ?? ucfirst($code) }}</span>
                            <span class="text-muted d-block">{{ $def['description'] ?? '' }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6 class="delay-ews-legend-heading">Escalation level</h6>
                    <ul class="list-unstyled mb-0 delay-ews-legend-list">
                        @foreach($escalationDefs as $level => $def)
                        <li class="mb-1">
                            <strong>{{ $def['label'] ?? 'Level ' . $level }}</strong>
                            <span class="text-muted d-block">{{ $def['description'] ?? '' }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
