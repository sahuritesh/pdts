@php
    $ctx = $data['ctx'];
    $delays = $data['delays'];
    $mitigations = $data['mitigations'];
    $rootCauses = $data['root_causes'];
    $statuses = $data['register_statuses'];
    $departmentTasks = $data['department_tasks'] ?? [];
    $tasksById = collect($departmentTasks)->keyBy('id');
    $encPd = Crypt::encrypt($ctx['id']);
    $rootCauseMap = collect($rootCauses)->keyBy('id');
    $statusMap = collect($statuses)->pluck('label', 'value');
    $severityClass = ['minor' => 'secondary', 'moderate' => 'warning', 'critical' => 'danger', 'showstopper' => 'dark'];
@endphp
<div class="sidelayout-panel delay-register-panel"
    data-panel-url="{{ getProjectUrl('projects/wizard/panel/delay/' . $encPd) }}"
    data-panel-title="{{ $pageTitle }}">
    <div class="sidelayout-context">{{ $ctx['project_code'] }} — {{ $ctx['department_name'] }}</div>

    @include('project_wizard.partials.delay-ews-legend')

    @if($delays->count())
    <h6>Delay Log History <span class="badge bg-light text-dark">{{ $delays->count() }}</span></h6>

    @foreach($delays as $delay)
    @php
        $rootLabel = trim($delay->root_cause_label ?? '');
        if ($rootLabel === '' && !empty($delay->root_cause_id) && $rootCauseMap->has($delay->root_cause_id)) {
            $rootLabel = $rootCauseMap[$delay->root_cause_id]->cause_name;
        }
        $statusLabel = $statusMap[$delay->register_status ?? 'open'] ?? ucfirst(str_replace('_', ' ', $delay->register_status ?? 'open'));
        $sev = strtolower($delay->severity ?? 'minor');
        $loggedAt = !empty($delay->created_on) && $delay->created_on !== '0000-00-00 00:00:00'
            ? date('d M Y, h:i A', strtotime($delay->created_on)) : '—';
        $updatedAt = !empty($delay->updated_on) && $delay->updated_on !== '0000-00-00 00:00:00'
            && $delay->updated_on !== $delay->created_on
            ? date('d M Y, h:i A', strtotime($delay->updated_on)) : null;
        $startDate = !empty($delay->delay_start_date) ? date('d M Y', strtotime($delay->delay_start_date)) : '—';
        $endDate = !empty($delay->delay_end_date) ? date('d M Y', strtotime($delay->delay_end_date)) : '—';
        $targetDate = !empty($delay->target_revised_completion_date)
            ? date('d M Y', strtotime($delay->target_revised_completion_date)) : '—';
        $linkedTaskId = (int) ($delay->project_department_task_id ?? 0);
        $linkedTaskName = $linkedTaskId > 0 && $tasksById->has($linkedTaskId)
            ? ($tasksById[$linkedTaskId]['task_name'] ?? '')
            : '';
        $impactedTaskLabel = $linkedTaskName !== '' ? $linkedTaskName : trim($delay->impacted_task ?? '');
    @endphp
    <div class="delay-log-card card border mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-2">
                <div>
                    <h6 class="delay-log-title mb-1">{{ $delay->delay_title }}</h6>
                    <div class="delay-log-meta">
                        <i class="ri-time-line"></i> Logged: <strong>{{ $loggedAt }}</strong>
                        @if($updatedAt)
                        <span class="mx-1">·</span> Updated: <strong>{{ $updatedAt }}</strong>
                        @endif
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-1 justify-content-end">
                    <span class="badge bg-{{ $severityClass[$sev] ?? 'secondary' }}">{{ ucfirst($sev) }}</span>
                    <span class="badge bg-info text-dark">{{ (int)($delay->delay_days ?? 0) }} days</span>
                    <span class="badge bg-primary">{{ $statusLabel }}</span>
                </div>
            </div>

            <div class="delay-log-details">
                @if(!empty($delay->primary_delay_drivers))
                <div class="delay-log-field">
                    <span class="delay-log-label">Primary Delay Driver(s)</span>
                    <p class="delay-log-value">{{ $delay->primary_delay_drivers }}</p>
                </div>
                @endif

                @if(!empty($delay->specific_event_description))
                <div class="delay-log-field">
                    <span class="delay-log-label">Event Description</span>
                    <p class="delay-log-value">{{ $delay->specific_event_description }}</p>
                </div>
                @endif

                @if($impactedTaskLabel !== '')
                <div class="delay-log-field">
                    <span class="delay-log-label">Impacted Task</span>
                    <p class="delay-log-value">
                        {{ $impactedTaskLabel }}
                        @if($linkedTaskId > 0)
                        <span class="badge bg-info-subtle text-info ms-1">Linked task</span>
                        @endif
                    </p>
                </div>
                @endif

                @if(!empty($delay->delay_description))
                <div class="delay-log-field">
                    <span class="delay-log-label">Description</span>
                    <p class="delay-log-value">{{ $delay->delay_description }}</p>
                </div>
                @endif

                <div class="row g-2 delay-log-grid">
                    <div class="col-md-6">
                        <span class="delay-log-label">Delay Start</span>
                        <p class="delay-log-value mb-0">{{ $startDate }}</p>
                    </div>
                    <div class="col-md-6">
                        <span class="delay-log-label">Delay End</span>
                        <p class="delay-log-value mb-0">{{ $endDate }}</p>
                    </div>
                    <div class="col-md-6">
                        <span class="delay-log-label">Root Cause</span>
                        <p class="delay-log-value mb-0">{{ $rootLabel !== '' ? $rootLabel : '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <span class="delay-log-label">Target Revised Completion</span>
                        <p class="delay-log-value mb-0">{{ $targetDate }}</p>
                    </div>
                    @if(!empty($delay->responsibility_name))
                    <div class="col-md-6">
                        <span class="delay-log-label">Responsibility</span>
                        <p class="delay-log-value mb-0">{{ $delay->responsibility_name }}</p>
                    </div>
                    @endif
                    @if(!empty($delay->alert_level))
                    @php
                        $alertDef = delayEwsDefinition('alert_levels', $delay->alert_level);
                        $alertBadge = $alertDef['badge_class'] ?? 'secondary';
                    @endphp
                    <div class="col-md-6">
                        <span class="delay-log-label">Alert Level</span>
                        <p class="delay-log-value mb-0">
                            <span class="badge bg-{{ $alertBadge }}">{{ delayEwsLabel('alert_levels', $delay->alert_level, ucfirst($delay->alert_level)) }}</span>
                        </p>
                    </div>
                    @endif
                    @if(!empty($delay->escalation_level))
                    <div class="col-md-6">
                        <span class="delay-log-label">Escalation Level</span>
                        <p class="delay-log-value mb-0">{{ delayEwsLabel('escalation_levels', $delay->escalation_level, 'Level ' . (int) $delay->escalation_level) }}</p>
                    </div>
                    @endif
                </div>
            </div>

            @if(isset($mitigations[$delay->id]) && count($mitigations[$delay->id]))
            <div class="delay-mitigations mt-3 pt-3 border-top">
                <span class="delay-log-label d-block mb-2">Mitigations ({{ count($mitigations[$delay->id]) }})</span>
                @foreach($mitigations[$delay->id] as $m)
                @php
                    $mLogged = !empty($m->created_on) && $m->created_on !== '0000-00-00 00:00:00'
                        ? date('d M Y, h:i A', strtotime($m->created_on)) : '—';
                    $mTarget = !empty($m->target_resolution_date)
                        ? date('d M Y', strtotime($m->target_resolution_date)) : '—';
                @endphp
                <div class="delay-mitigation-item">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                        <strong class="small">{{ $m->mitigation_action }}</strong>
                        <span class="badge bg-light text-dark text-capitalize flex-shrink-0">{{ str_replace('_', ' ', $m->current_status ?? 'open') }}</span>
                    </div>
                    <div class="small text-muted">
                        @if(!empty($m->owner_name))<span>Owner: {{ $m->owner_name }}</span><span class="mx-1">·</span>@endif
                        <span>Target: {{ $mTarget }}</span><span class="mx-1">·</span>
                        <span>Logged: {{ $mLogged }}</span>
                    </div>
                    @if(!empty($m->resolution_remarks))
                    <p class="small mb-0 mt-1 text-muted"><em>{{ $m->resolution_remarks }}</em></p>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @endforeach
    @else
    <div class="alert alert-light border small mb-3">No delay entries logged for this department yet.</div>
    @endif

    <h6>Log Delay</h6>
    <form id="wizardDelayForm" class="custom-validations">
        @csrf
        <input type="hidden" name="project_department_id" value="{{ $ctx['id'] }}">
        <div class="row">
            <div class="col-12 mb-2">
                <label class="required-label">Delay Title</label>
                <input type="text" class="form-control required" name="delay_title">
            </div>
            <div class="col-12 mb-2">
                <label>Primary Delay Driver(s)</label>
                <textarea class="form-control" name="primary_delay_drivers" rows="2"></textarea>
            </div>
            <div class="col-12 mb-2">
                <label>Event Description</label>
                <textarea class="form-control" name="specific_event_description" rows="2"></textarea>
            </div>
            @include('project_wizard.partials.dept-delay-task-select', [
                'projectDepartmentId' => $ctx['id'],
                'tasks' => $departmentTasks,
            ])
            <div class="col-12 mb-2">
                <div class="row g-2 planned-date-range">
                    <div class="col-md-6">
                        <label for="delay_start_{{ $ctx['id'] }}">Start Date</label>
                        <input type="date" class="form-control js-planned-start" name="delay_start_date" id="delay_start_{{ $ctx['id'] }}" autocomplete="off" placeholder="yyyy-mm-dd">
                    </div>
                    <div class="col-md-6">
                        <label for="delay_end_{{ $ctx['id'] }}">End Date</label>
                        <input type="date" class="form-control js-planned-end" name="delay_end_date" id="delay_end_{{ $ctx['id'] }}" data-label="Delay end date" autocomplete="off" placeholder="yyyy-mm-dd">
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-2">
                <label>Root Cause</label>
                <select name="root_cause_id" class="form-control dd-select">
                    <option value="">Select</option>
                    @foreach($rootCauses as $rc)
                    <option value="{{ $rc->id }}">{{ $rc->cause_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-2">
                <label>Status</label>
                <select name="register_status" class="form-control dd-select">
                    @foreach($statuses as $st)
                    <option value="{{ $st['value'] }}">{{ $st['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="licensing_openings_affected" value="1" id="licensing_{{ $ctx['id'] }}">
                    <label class="form-check-label" for="licensing_{{ $ctx['id'] }}">Is this a Showstopper?</label>
                </div>
            </div>
        </div>
        <div class="sidelayout-actions">
            <button type="button" class="btn btn-submit btn-sm" id="saveWizardDelayBtn">Save Delay</button>
        </div>
    </form>

    <hr>
    @if($delays->count())
    <h6>Add Mitigation</h6>
    <form id="wizardMitigationForm">
        @csrf
        <div class="row">
            <div class="col-12 mb-2">
                <label class="required-label">For Delay Entry</label>
                <select name="delay_register_id" class="form-control dd-select required">
                    <option value="">Select delay</option>
                    @foreach($delays as $delay)
                    <option value="{{ $delay->id }}">{{ $delay->delay_title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 mb-2">
                <label class="required-label">Mitigation Action</label>
                <textarea class="form-control required" name="mitigation_action" rows="2"></textarea>
            </div>
            <div class="col-md-6 mb-2">
                <label>Owner</label>
                <input type="text" class="form-control" name="action_owner">
            </div>
            <div class="col-md-6 mb-2">
                <label>Target Date</label>
                <input type="date" class="form-control" name="target_completion_date">
            </div>
        </div>
        <div class="sidelayout-actions">
            <button type="button" class="btn btn-submit btn-sm" id="saveWizardMitigationBtn">Save Mitigation</button>
        </div>
    </form>
    @else
    <h6>Mitigation</h6>
    <div class="alert alert-info small mb-0">
        <i class="ri-information-line me-1"></i>
        No delay entries are registered for this department yet. Log a delay above before adding mitigation actions.
    </div>
    @endif
</div>

<style>
.delay-register-panel .delay-log-card {
    background: #fafbfc;
}
.delay-register-panel .delay-log-title {
    font-size: 0.95rem;
    font-weight: 600;
    color: #343a40;
}
.delay-register-panel .delay-log-meta {
    font-size: 0.75rem;
    color: #878a99;
}
.delay-register-panel .delay-log-meta i {
    margin-right: 0.2rem;
}
.delay-register-panel .delay-log-label {
    display: block;
    font-size: 0.68rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #878a99;
    margin-bottom: 0.2rem;
}
.delay-register-panel .delay-log-value {
    font-size: 0.85rem;
    color: #495057;
    margin-bottom: 0.65rem;
    white-space: pre-wrap;
    word-break: break-word;
}
.delay-register-panel .delay-log-grid {
    margin-top: 0.25rem;
    padding-top: 0.5rem;
    border-top: 1px dashed #e9ecef;
}
.delay-register-panel .delay-mitigation-item {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 0.6rem 0.75rem;
    margin-bottom: 0.5rem;
}
.delay-register-panel .delay-mitigation-item:last-child {
    margin-bottom: 0;
}
.delay-register-panel .delay-ews-legend-heading {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #878a99;
    margin-bottom: 0.35rem;
}
.delay-register-panel .delay-ews-legend-list li {
    line-height: 1.35;
}
.delay-register-panel .delay-ews-legend-toggle[aria-expanded="true"] .delay-ews-legend-chevron {
    transform: rotate(180deg);
}
</style>

<script>
(function initDelayRegisterPanel() {
    function getDelayPanel() {
        return $('#dynamicSideLayoutContent .delay-register-panel').first();
    }

    function reloadPanel() {
        var $panel = getDelayPanel();
        var url = $panel.data('panel-url');
        var title = $panel.data('panel-title') || 'Delay Register';
        if (!url) {
            return;
        }
        openSideLayout({}, url, title);
    }

    if (!window.__wizardDelayHandlersBound) {
        window.__wizardDelayHandlersBound = true;

        $(document).on('click.wizardDelaySave', '#saveWizardDelayBtn', function() {
            if (window.__wizardDelaySaving) {
                return;
            }

            var $btn = $(this);
            var $panel = getDelayPanel();
            var $form = $panel.find('#wizardDelayForm');
            if (!$panel.length || !$form.length) {
                return;
            }
            if (typeof validatePlannedDateRangesInScope === 'function' && !validatePlannedDateRangesInScope($form)) {
                return;
            }

            window.__wizardDelaySaving = true;
            $btn.prop('disabled', true);

            ajaxRequestWithPromise(@json(getProjectUrl('wizard_save_delay')), $form.serialize(), 'wizard_save_delay', 0, '', $btn)
                .then(function(res) {
                    if (res.error == 0 || res.error == '0') {
                        $form[0].reset();
                        if ($form.find('.dd-select').length && $.fn.select2) {
                            $form.find('.dd-select').val('').trigger('change');
                        }
                        reloadPanel();
                    }
                })
                .finally(function() {
                    window.__wizardDelaySaving = false;
                    $btn.prop('disabled', false);
                });
        });

        $(document).on('click.wizardMitigationSave', '#saveWizardMitigationBtn', function() {
            if (window.__wizardMitigationSaving) {
                return;
            }

            var $btn = $(this);
            var $panel = getDelayPanel();
            if (!$panel.length) {
                return;
            }

            window.__wizardMitigationSaving = true;
            $btn.prop('disabled', true);

            ajaxRequestWithPromise(@json(getProjectUrl('wizard_save_mitigation')), $panel.find('#wizardMitigationForm').serialize(), 'wizard_save_mitigation', 0, '', $btn)
                .then(function(res) {
                    if (res.error == 0 || res.error == '0') {
                        reloadPanel();
                    }
                })
                .finally(function() {
                    window.__wizardMitigationSaving = false;
                    $btn.prop('disabled', false);
                });
        });
    }

    var $panel = getDelayPanel();
    if (!$panel.length) {
        return;
    }

    $('.sidelayoutTitle').html($panel.data('panel-title') || @json($pageTitle));
    if ($.fn.select2) {
        $panel.find('.dd-select').select2({ dropdownParent: $('#offcanvasRight'), width: '100%' });
    }
})();
</script>
