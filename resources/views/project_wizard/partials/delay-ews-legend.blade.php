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
           <div class="row g-3 delay-ews-info-box">

    <div class="col-md-4">
        <div class="legend-card">
            <h6 class="delay-ews-legend-heading">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Severity
            </h6>

            <ul class="list-unstyled mb-0 delay-ews-legend-list">
                @foreach($severityDefs as $code => $def)
                <li>
                    <strong>{{ $def['label'] ?? ucfirst($code) }}</strong>
                    <span>{{ $def['description'] ?? '' }}</span>
                </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="col-md-4">
        <div class="legend-card">
            <h6 class="delay-ews-legend-heading">
                <i class="fas fa-bell me-2"></i>
                Alert Level
            </h6>

            <ul class="list-unstyled mb-0 delay-ews-legend-list">
                @foreach($alertDefs as $code => $def)
                <li>
                    <span class="badge bg-{{ $def['badge_class'] ?? 'secondary' }}">
                        {{ $def['label'] ?? ucfirst($code) }}
                    </span>

                    <span>{{ $def['description'] ?? '' }}</span>
                </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="col-md-4">
        <div class="legend-card">
            <h6 class="delay-ews-legend-heading">
                <i class="fas fa-layer-group me-2"></i>
                Escalation Level
            </h6>

            <ul class="list-unstyled mb-0 delay-ews-legend-list">
                @foreach($escalationDefs as $level => $def)
                <li>
                    <strong>{{ $def['label'] ?? 'Level '.$level }}</strong>
                    <span>{{ $def['description'] ?? '' }}</span>
                </li>
                @endforeach
            </ul>
        </div>
    </div>

</div>
        </div>
    </div>
</div>

<style>
/* ===== Parent ===== */

.delay-ews-info-box{
    margin-top:15px;
}

/* ===== Card ===== */

.delay-ews-info-box .legend-card{

    background:#ffffff;

    border:1px solid #e5e7eb;

    border-radius:14px;

    padding:20px;

    height:100%;

    transition:.35s;

    box-shadow:
        0 4px 18px rgba(15,23,42,.06);
}

.delay-ews-info-box .legend-card:hover{

    transform:translateY(-5px);

    border-color:#2563eb;

    box-shadow:
        0 12px 30px rgba(37,99,235,.15);
}

/* ===== Heading ===== */

.delay-ews-info-box .delay-ews-legend-heading{

    display:flex;

    align-items:center;

    gap:8px;

    font-size:16px;

    font-weight:700;

    color:#0f172a;

    margin-bottom:18px;

    padding-bottom:12px;

    border-bottom:1px solid #edf2f7;
}

.delay-ews-info-box .delay-ews-legend-heading i{

    width:36px;
    height:36px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:10px;
    color:#fff;
    background: linear-gradient(135deg, #0f8c91, #1d4ed8);
    font-size:15px;
}

/* ===== List ===== */

.delay-ews-info-box .delay-ews-legend-list li{

    padding:12px 0;

    border-bottom:1px dashed #e5e7eb;
}

.delay-ews-info-box .delay-ews-legend-list li:last-child{

    border-bottom:none;
}

.delay-ews-info-box .delay-ews-legend-list strong{
    display:block;
    font-size:12px;
    color:#111827;
    margin-bottom:0px;
}

.delay-ews-info-box .delay-ews-legend-list span{
    font-size:12px;
    color:#6b7280;
    line-height:1.6;
}
.delay-ews-info-box .delay-ews-legend-list .bg-success, .delay-ews-info-box .delay-ews-legend-list .bg-danger, .delay-ews-info-box .delay-ews-legend-list .bg-danger, .delay-ews-info-box .delay-ews-legend-list .bg-dark{
    color:#fff !important;
    display: block;
}

/* ===== Badge ===== */

.delay-ews-info-box .badge{

    padding:6px 12px;

    border-radius:30px;

    font-size:12px;

    font-weight:600;

    margin-bottom:6px;
}
    </style>