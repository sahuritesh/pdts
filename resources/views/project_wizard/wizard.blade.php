@extends('layouts.template_v1')
@section('content')
@php
    $project = $data['project'] ?? null;
    $projectId = $data['project_id'] ?? ($project['id'] ?? 0);
    $projectDepartments = $data['project_departments'] ?? [];
    $masterDepartments = $data['master_departments'] ?? [];
    $statusLabels = $data['status_labels'] ?? [];
    $projectTypes = $data['project_types'] ?? [];
    $zones = $data['zones'] ?? [];
    $selectedDeptIds = array_column($projectDepartments, 'department_id');
    $selectedZoneId = $project['zone_id'] ?? '';
    $enableClick = !empty($projectId);
    $initialStep = $enableClick ? max(0, min(2, (int) ($project['wizard_step'] ?? 1) - 1)) : 0;
    $activeExpand = null;
    foreach ($projectDepartments as $pd) {
        if (in_array($pd['department_status'] ?? '', ['start', 'in_progress', 'delay'])) {
            $activeExpand = $pd['id'];
            break;
        }
    }
@endphp

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">{{ $pageTitle }}</h4>
                    <a href="{{ getProjectUrl('projects-list') }}" class="text-muted small"><i class="ri-arrow-left-line"></i> Back to projects</a>
                </div>

                <input type="hidden" id="hdnForeignKeyId" value="{{ $projectId }}">
                <a id="commonActionButton" href="{{ getProjectUrl('projects-list') }}" style="display:none"></a>

                <div id="basic-example" role="application" class="wizard clearfix">
                    <div class="steps clearfix">
                        <ul role="tablist">
                            <li role="tab" class="first customWizard customWizard-0 @if($initialStep === 0) current @else disabled @endif"
                                @if($enableClick) onclick="enableDisableSections(0)" @endif>
                                <a class="customWizardSteps"><span class="number">1.</span> General</a>
                            </li>
                            <li role="tab" class="customWizard customWizard-1 @if($initialStep === 1) current @else disabled @endif"
                                @if($enableClick) onclick="enableDisableSections(1)" @endif>
                                <a class="customWizardSteps"><span class="number">2.</span> Departments</a>
                            </li>
                            <li role="tab" class="customWizard customWizard-2 @if($initialStep === 2) current @else disabled @endif"
                                @if($enableClick) onclick="enableDisableSections(2)" @endif>
                                <a class="customWizardSteps"><span class="number">3.</span> Execution</a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="clearfix">&nbsp;</div>

                {{-- Step 1: General --}}
                <section class="commonSections section0" @if($initialStep !== 0) style="display:none" @endif>
                    <form id="masterForm0" class="masterForm" data-url="save_wizard_step1" enctype="multipart/form-data">
                        @csrf
                        <h5 class="mb-3">Project — General Information</h5>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label class="required-label">Project ID</label>
                                <input type="text" class="form-control required" name="project_code" value="{{ $project['project_code'] ?? '' }}">
                            </div>
                            <div class="col-md-8 mb-2">
                                <label class="required-label">Project Name</label>
                                <input type="text" class="form-control required" name="project_name" value="{{ $project['project_name'] ?? '' }}">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Hospital Name</label>
                                <input type="text" class="form-control" name="hospital_name" value="{{ $project['hospital_name'] ?? '' }}">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Project Type</label>
                                <select name="project_type_id" class="form-control dd-select">
                                    <option value="">Select type</option>
                                    @foreach($projectTypes as $type)
                                    <option value="{{ $type['id'] }}" @if(($project['project_type_id'] ?? '') == $type['id']) selected @endif>{{ $type['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Zone</label>
                                <select name="zone_id" class="form-control dd-select">
                                    <option value="">Select zone</option>
                                    @foreach($zones as $zone)
                                    <option value="{{ $zone['id'] }}" @if($selectedZoneId == $zone['id']) selected @endif>{{ $zone['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Area / Facility</label>
                                <input type="text" class="form-control" name="area_facility" value="{{ $project['area_facility'] ?? '' }}">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Location</label>
                                <input type="text" class="form-control" name="location" value="{{ $project['location'] ?? '' }}">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Project SPOC</label>
                                <input type="text" class="form-control" name="project_spoc_name" value="{{ $project['project_spoc_name'] ?? '' }}">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Contractor</label>
                                <input type="text" class="form-control" name="contractor_name" value="{{ $project['contractor_name'] ?? '' }}">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Planned Start</label>
                                <input type="date" class="form-control" name="planned_start_date" value="{{ !empty($project['planned_start_date']) ? date('Y-m-d', strtotime($project['planned_start_date'])) : '' }}">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Planned Completion</label>
                                <input type="date" class="form-control" name="planned_completion_date" value="{{ !empty($project['planned_completion_date']) ? date('Y-m-d', strtotime($project['planned_completion_date'])) : '' }}">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Target Revised Completion</label>
                                <input type="date" class="form-control" name="target_revised_completion_date" value="{{ !empty($project['target_revised_completion_date']) ? date('Y-m-d', strtotime($project['target_revised_completion_date'])) : '' }}">
                            </div>
                            <div class="col-md-12 mb-2">
                                <label>Project Scope</label>
                                <textarea class="form-control" name="project_scope" rows="3">{{ $project['project_scope'] ?? '' }}</textarea>
                            </div>
                        </div>
                        <div class="wizard">
                            <div class="actions clearfix">
                                <ul role="menu">
                                    <li class="disabled" aria-disabled="true"><a href="#previous">Previous</a></li>
                                    <li onclick="calculateSteps(this,'next',0)"><a href="#next">Next</a></li>
                                </ul>
                            </div>
                        </div>
                        <input type="hidden" class="masterId" name="project_id" id="projectMasterId" value="{{ $projectId }}">
                    </form>
                </section>

                {{-- Step 2: Departments --}}
                <section class="commonSections section1" @if($initialStep !== 1) style="display:none" @endif>
                    <form id="masterForm1" class="masterForm" data-url="save_wizard_departments" enctype="multipart/form-data">
                        @csrf
                        <h5 class="mb-3">Select &amp; Order Departments</h5>
                        <div class="row">
                            <div class="col-md-5">
                                <div class="border rounded p-3" style="max-height:480px;overflow-y:auto;">
                                    @foreach($masterDepartments as $dept)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input dept-pick" type="checkbox" value="{{ $dept['id'] }}" id="dept_pick_{{ $dept['id'] }}"
                                            @if(in_array($dept['id'], $selectedDeptIds)) checked @endif>
                                        <label class="form-check-label" for="dept_pick_{{ $dept['id'] }}">
                                            <strong>{{ $dept['department_name'] }}</strong>
                                            @if(!empty($dept['description']))<br><small class="text-muted">{{ $dept['description'] }}</small>@endif
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-7">
                                <p class="text-muted small">Drag to set execution order (top = first).</p>
                                <ul id="deptSortable" class="list-group dept-sortable mb-0">
                                    @foreach($projectDepartments as $pd)
                                    <li class="list-group-item d-flex justify-content-between align-items-center" data-dept-id="{{ $pd['department_id'] }}">
                                        <span><i class="ri-drag-move-2-line me-2 text-muted"></i>{{ $pd['department_name'] }}</span>
                                        <button type="button" class="btn btn-sm btn-link text-danger remove-dept">&times;</button>
                                    </li>
                                    @endforeach
                                </ul>
                                <p class="text-muted small mt-2 mb-0" id="deptSortableEmpty" @if(count($projectDepartments)) style="display:none" @endif>Select departments from the left.</p>
                            </div>
                        </div>
                        <input type="hidden" name="department_order" id="department_order" value="">
                        <div class="wizard mt-3">
                            <div class="actions clearfix">
                                <ul role="menu">
                                    <li onclick="calculateSteps(this,'previous',1)"><a href="#previous">Previous</a></li>
                                    <li onclick="calculateSteps(this,'next',1,'','initProjectExecutionStep')"><a href="#next">Next</a></li>
                                </ul>
                            </div>
                        </div>
                        <input type="hidden" class="masterId" name="project_id" id="projectDeptMasterId" value="{{ $projectId }}">
                    </form>
                </section>

                {{-- Step 3: Execution --}}
                <section class="commonSections section2" @if($initialStep !== 2) style="display:none" @endif>
                    <form id="masterForm2" class="masterForm" data-url="save_wizard_finish" enctype="multipart/form-data">
                        @csrf
                        <h5 class="mb-3">Department Execution</h5>
                        @if(empty($projectDepartments))
                        <div class="alert alert-info">Save departments in step 2 to begin execution tracking.</div>
                        @else
                        <div class="accordion" id="deptExecutionAccordion">
                            @foreach($projectDepartments as $index => $pd)
                            @php
                                $status = $pd['department_status'] ?? 'pending';
                                $isPending = $status === 'pending';
                                $expanded = ($activeExpand && $activeExpand == $pd['id']) || (!$activeExpand && $index === 0 && !$isPending);
                                $encPdId = Crypt::encrypt($pd['id']);
                                $badgeClass = ['pending'=>'secondary','start'=>'info','in_progress'=>'primary','delay'=>'warning','completed'=>'success'][$status] ?? 'secondary';
                            @endphp
                            <div class="accordion-item dept-accordion-item {{ $status }} mb-2 border">
                                <h2 class="accordion-header">
                                    <button class="accordion-button @if(!$expanded) collapsed @endif @if($isPending) disabled @endif" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#collapse_{{ $pd['id'] }}"
                                        @if($isPending) disabled @endif>
                                        <span class="me-2">{{ $pd['sort_order'] }}.</span>
                                        <strong>{{ $pd['department_name'] }}</strong>
                                        <span class="badge bg-{{ $badgeClass }} ms-2">{{ $statusLabels[$status] ?? ucfirst($status) }}</span>
                                    </button>
                                </h2>
                                <div id="collapse_{{ $pd['id'] }}" class="accordion-collapse collapse @if($expanded) show @endif" data-bs-parent="#deptExecutionAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <p class="text-muted small">{{ $pd['department_description'] ?? '' }}</p>
                                                @if(!$isPending)
                                                <div class="dept-meta-form row g-2 mb-2" data-pd-id="{{ $pd['id'] }}">
                                                    <input type="hidden" name="project_department_id" value="{{ $pd['id'] }}">
                                                    <div class="col-md-4">
                                                        <label class="small text-muted">SPOC</label>
                                                        <input type="text" class="form-control form-control-sm" name="spoc_name" value="{{ $pd['spoc_name'] ?? '' }}">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="small text-muted">Planned start</label>
                                                        <input type="date" class="form-control form-control-sm" name="planned_start_date"
                                                            value="{{ !empty($pd['planned_start_date']) ? date('Y-m-d', strtotime($pd['planned_start_date'])) : '' }}">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="small text-muted">Planned end</label>
                                                        <input type="date" class="form-control form-control-sm" name="planned_end_date"
                                                            value="{{ !empty($pd['planned_end_date']) ? date('Y-m-d', strtotime($pd['planned_end_date'])) : '' }}">
                                                    </div>
                                                    <div class="col-md-2 d-flex align-items-end">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary save-dept-meta w-100">Save</button>
                                                    </div>
                                                </div>
                                                @endif
                                                @if(in_array($status, ['start', 'in_progress', 'delay']))
                                                <div class="btn-group btn-group-sm mb-2">
                                                    <button type="button" class="btn btn-outline-primary dept-action" data-id="{{ $pd['id'] }}" data-action="in_progress">In Progress</button>
                                                    <button type="button" class="btn btn-outline-success dept-action" data-id="{{ $pd['id'] }}" data-action="complete">Complete</button>
                                                </div>
                                                @endif
                                            </div>
                                            <div class="col-md-4">
                                                <div class="dept-panel-actions">
                                                    <p class="dept-panel-actions-label">Department actions</p>
                                                    <button type="button" class="dept-panel-card wizard-panel-btn dept-panel-delay"
                                                        data-url="{{ getProjectUrl('projects/wizard/panel/delay/' . $encPdId) }}"
                                                        data-title="Delay — {{ $pd['department_name'] }}" @if($isPending) disabled @endif>
                                                        <span class="dept-panel-icon"><i class="ri-alarm-warning-line"></i></span>
                                                        <span class="dept-panel-text">
                                                            <strong>Delay Register</strong>
                                                            <small>Log delays &amp; mitigations</small>
                                                        </span>
                                                        <i class="ri-arrow-right-s-line dept-panel-arrow"></i>
                                                    </button>
                                                    <button type="button" class="dept-panel-card wizard-panel-btn dept-panel-financial"
                                                        data-url="{{ getProjectUrl('projects/wizard/panel/financial/' . $encPdId) }}"
                                                        data-title="Financial — {{ $pd['department_name'] }}" @if($isPending) disabled @endif>
                                                        <span class="dept-panel-icon"><i class="ri-money-dollar-circle-line"></i></span>
                                                        <span class="dept-panel-text">
                                                            <strong>Financial Impact</strong>
                                                            <small>Cost &amp; budget impact</small>
                                                        </span>
                                                        <i class="ri-arrow-right-s-line dept-panel-arrow"></i>
                                                    </button>
                                                    <button type="button" class="dept-panel-card wizard-panel-btn dept-panel-attachments"
                                                        data-url="{{ getProjectUrl('projects/wizard/panel/attachments/' . $encPdId) }}"
                                                        data-title="Attachments — {{ $pd['department_name'] }}" @if($isPending) disabled @endif>
                                                        <span class="dept-panel-icon"><i class="ri-attachment-2-line"></i></span>
                                                        <span class="dept-panel-text">
                                                            <strong>Attachments</strong>
                                                            <small>Documents &amp; files</small>
                                                        </span>
                                                        <i class="ri-arrow-right-s-line dept-panel-arrow"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                        <div class="wizard mt-3">
                            <div class="actions clearfix">
                                <ul role="menu">
                                    <li onclick="calculateSteps(this,'previous',2)"><a href="#previous">Previous</a></li>
                                    <li onclick="calculateSteps(this,'finish',2,'{{ getProjectUrl('projects-list') }}')"><a href="#finish">Finish</a></li>
                                </ul>
                            </div>
                        </div>
                        <input type="hidden" class="masterId" name="project_id" id="projectFinishMasterId" value="{{ $projectId }}">
                    </form>
                </section>

            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
/* Project wizard — step nav (matches CI form-wizard pattern) */
#basic-example.wizard {
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e9ecef;
}
#basic-example .steps > ul {
    display: flex;
    flex-wrap: wrap;
    list-style: none;
    padding: 0;
    margin: 0;
    gap: 0;
}
#basic-example .steps > ul > li {
    flex: 1 1 0;
    min-width: 0;
    position: relative;
    text-align: center;
}
#basic-example .steps > ul > li:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 22px;
    left: calc(50% + 28px);
    right: calc(-50% + 28px);
    height: 3px;
    background: #d6dee8;
    z-index: 0;
}
#basic-example .steps > ul > li.current:not(:last-child)::after,
#basic-example .steps > ul > li.stepsCompleted:not(:last-child)::after {
    background: #49b96f;
}
#basic-example .steps > ul > li a.customWizardSteps {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.75rem 0.5rem 1rem;
    color: #6c757d;
    font-weight: 500;
    text-decoration: none;
    background: transparent;
    position: relative;
    z-index: 1;
}
#basic-example .steps .number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    line-height: 1;
    margin: 0 auto 0.5rem;
    border: 2px solid #b5c3cd;
    color: #6c757d;
    background: #fff;
    border-radius: 50%;
    font-size: 0.95rem;
    font-weight: 600;
}
#basic-example .steps > ul > li.current a {
    color: #405189;
    font-weight: 600;
}
#basic-example .steps > ul > li.current .number {
    border-color: #405189;
    background: #405189;
    color: #fff;
}
#basic-example .steps > ul > li.stepsCompleted .number,
#basic-example .steps > ul > li:not(.current):not(.disabled) .number {
    border-color: #49b96f;
    background: #49b96f;
    color: #fff;
}
#basic-example .steps > ul > li.stepsCompleted a,
#basic-example .steps > ul > li:not(.current):not(.disabled) a {
    color: #2e7d4a;
}
#basic-example .steps > ul > li.disabled a {
    color: #adb5bd;
    cursor: default;
}
#basic-example .steps > ul > li.disabled .number {
    border-color: #dee2e6;
    background: #f8f9fa;
    color: #adb5bd;
}
#basic-example .steps > ul > li.disabled:not([onclick]) {
    pointer-events: none;
}
#basic-example .steps > ul > li[onclick] {
    cursor: pointer;
}
@media (max-width: 767.98px) {
    #basic-example .steps > ul {
        flex-direction: column;
        gap: 0.25rem;
    }
    #basic-example .steps > ul > li:not(:last-child)::after {
        display: none;
    }
    #basic-example .steps > ul > li a.customWizardSteps {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        text-align: left;
        padding: 0.5rem 0;
    }
    #basic-example .steps .number {
        margin: 0;
        flex-shrink: 0;
    }
}

/* Wizard footer buttons */
.commonSections .wizard > .actions > ul {
    list-style: none;
    padding: 0;
    margin: 1.25rem 0 0;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.commonSections .wizard > .actions > ul > li {
    display: inline-block;
    margin: 0;
}
.commonSections .wizard > .actions a {
    display: inline-block;
    padding: 0.5rem 1.25rem;
    background: #405189;
    color: #fff !important;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 500;
    border: none;
}
.commonSections .wizard > .actions a:hover {
    background: #364574;
    color: #fff;
}
.commonSections .wizard > .actions > ul > li.disabled a {
    background: #ced4da;
    color: #6c757d !important;
    pointer-events: none;
    cursor: not-allowed;
}

.dept-sortable li { cursor: move; }
.dept-accordion-item.pending .accordion-button { pointer-events: none; opacity: .65; }

/* Department execution — right-side action cards */
.dept-panel-actions {
    background: #f8fafc;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 0.85rem;
    height: 100%;
}
.dept-panel-actions-label {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #878a99;
    margin: 0 0 0.65rem 0.25rem;
}
.dept-panel-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
    text-align: left;
    padding: 0.7rem 0.85rem;
    margin-bottom: 0.5rem;
    border: 1px solid #e3e8ef;
    border-radius: 8px;
    background: #fff;
    color: #495057;
    transition: border-color 0.2s, box-shadow 0.2s, transform 0.15s;
    cursor: pointer;
}
.dept-panel-card:last-child { margin-bottom: 0; }
.dept-panel-card:hover:not(:disabled) {
    border-color: #c5cdd8;
    box-shadow: 0 4px 12px rgba(64, 81, 137, 0.1);
    transform: translateY(-1px);
}
.dept-panel-card:disabled {
    opacity: 0.55;
    cursor: not-allowed;
    background: #f1f3f5;
}
.dept-panel-icon {
    flex-shrink: 0;
    width: 38px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 1.15rem;
}
.dept-panel-delay .dept-panel-icon {
    background: rgba(247, 184, 75, 0.15);
    color: #e8a317;
}
.dept-panel-financial .dept-panel-icon {
    background: rgba(20, 157, 164, 0.12);
    color: #149da4;
}
.dept-panel-attachments .dept-panel-icon {
    background: rgba(64, 81, 137, 0.1);
    color: #405189;
}
.dept-panel-text {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    line-height: 1.25;
}
.dept-panel-text strong {
    font-size: 0.875rem;
    font-weight: 600;
    color: #343a40;
}
.dept-panel-text small {
    font-size: 0.75rem;
    color: #878a99;
    margin-top: 0.15rem;
}
.dept-panel-arrow {
    flex-shrink: 0;
    font-size: 1.1rem;
    color: #adb5bd;
    transition: color 0.2s, transform 0.2s;
}
.dept-panel-card:hover:not(:disabled) .dept-panel-arrow {
    color: #405189;
    transform: translateX(2px);
}
.dept-panel-delay:hover:not(:disabled) { border-color: rgba(232, 163, 23, 0.45); }
.dept-panel-financial:hover:not(:disabled) { border-color: rgba(20, 157, 164, 0.45); }
.dept-panel-attachments:hover:not(:disabled) { border-color: rgba(64, 81, 137, 0.35); }
@media (max-width: 767.98px) {
    .dept-panel-actions {
        margin-top: 1rem;
    }
}
</style>
@endpush

<script>
var projectDeptMaster = @json($masterDepartments);
var projectWizardInitialStep = {{ $initialStep }};
var projectWizardEnableClick = {{ $enableClick ? 'true' : 'false' }};

function syncProjectDepartmentOrder() {
    var ids = [];
    $('#deptSortable li').each(function() { ids.push($(this).data('dept-id')); });
    $('#department_order').val(ids.join(','));
    $('#deptSortableEmpty').toggle(ids.length === 0);
}

function initProjectExecutionStep() {
    window.location.reload();
}

function bindExecutionPanelHandlers() {
    $('.wizard-panel-btn').off('click').on('click', function() {
        if ($(this).prop('disabled')) return;
        openSideLayout({}, $(this).data('url'), $(this).data('title'));
    });
    $('.dept-action').off('click').on('click', function() {
        var $btn = $(this);
        var payload = 'project_department_id=' + $btn.data('id') + '&action=' + $btn.data('action') + '&_token={{ csrf_token() }}';
        ajaxRequestWithPromise("{{ getProjectUrl('update_department_status') }}", payload, 'update_department_status', 0, '', $btn)
            .then(function(res) {
                parseFormErrors(res, res.error == 0 ? 'success' : 'error');
                if (res.error == 0) { setTimeout(function() { window.location.reload(); }, 600); }
            });
    });

    $('.save-dept-meta').off('click').on('click', function() {
        var $btn = $(this);
        var $block = $btn.closest('.dept-meta-form');
        var payload = new FormData();
        $block.find('input, select, textarea').each(function() {
            var name = this.name;
            if (name) {
                payload.append(name, $(this).val());
            }
        });
        payload.append('_token', '{{ csrf_token() }}');
        ajaxRequestWithPromise("{{ getProjectUrl('save_project_department') }}", payload, 'save_project_department', 1, '', $btn)
            .then(function(res) { parseFormErrors(res, res.error == 0 ? 'success' : 'error'); });
    });
}

$(function() {
    if ($.fn.select2) { $('.dd-select').select2({ width: '100%' }); }

    if (projectWizardEnableClick) {
        for (var i = 0; i <= 2; i++) {
            $('.customWizard-' + i).removeClass('disabled');
        }
    }
    enableDisableSections(projectWizardInitialStep);
    $('.customWizard-' + projectWizardInitialStep).addClass('current');

    $('#deptSortable').sortable({ placeholder: 'list-group-item bg-light' });
    syncProjectDepartmentOrder();

    $('.dept-pick').on('change', function() {
        var id = $(this).val();
        if ($(this).is(':checked')) {
            if (!$('#deptSortable li[data-dept-id="' + id + '"]').length) {
                var dept = projectDeptMaster.find(function(d) { return String(d.id) === String(id); });
                if (dept) {
                    $('#deptSortable').append(
                        '<li class="list-group-item d-flex justify-content-between align-items-center" data-dept-id="' + id + '">' +
                        '<span><i class="ri-drag-move-2-line me-2 text-muted"></i>' + dept.department_name + '</span>' +
                        '<button type="button" class="btn btn-sm btn-link text-danger remove-dept">&times;</button></li>'
                    );
                }
            }
        } else {
            $('#deptSortable li[data-dept-id="' + id + '"]').remove();
        }
        syncProjectDepartmentOrder();
    });

    $(document).on('click', '.remove-dept', function() {
        var id = $(this).closest('li').data('dept-id');
        $(this).closest('li').remove();
        $('#dept_pick_' + id).prop('checked', false);
        syncProjectDepartmentOrder();
    });

    bindExecutionPanelHandlers();
});
</script>
@endsection
