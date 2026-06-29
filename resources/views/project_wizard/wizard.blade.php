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
    $hospitals = $data['hospitals'] ?? [];
    $spocUsers = $data['spoc_users'] ?? [];
    $projectSpocUsers = $data['project_spoc_users'] ?? [];
    $selectedDeptIds = array_column($projectDepartments, 'department_id');
    $selectedZoneId = $project['zone_id'] ?? '';
    $selectedLocationId = $project['location_id'] ?? '';
    $selectedHospitalId = $project['hospital_id'] ?? '';
    if ($selectedHospitalId === '' && !empty($project['hospital_name']) && !empty($hospitals)) {
        foreach ($hospitals as $hospital) {
            if (($hospital['label'] ?? '') === $project['hospital_name']) {
                $selectedHospitalId = $hospital['id'];
                break;
            }
        }
    }
    $enableClick = !empty($projectId);
    $savedWizardStep = (int) ($project['wizard_step'] ?? 1);
    $initialStep = $enableClick ? max(0, min(2, $savedWizardStep - 1)) : 0;
    if ($enableClick && request()->query('step') === 'execution') {
        $initialStep = 2;
        $canOpenExecutionTab = true;
    }
    $canOpenDepartmentsTab = $enableClick && $savedWizardStep >= 2;
    $canOpenExecutionTab = $enableClick && $savedWizardStep >= 3;
    $isProjectReadOnly = !empty($data['read_only']);
    $departmentTasksByPd = $data['department_tasks_by_pd'] ?? [];
    $taskStatusLabels = $data['task_status_labels'] ?? [];
    $projectDeptService = app(\App\Services\ProjectDepartmentService::class);
    $projectDeptTaskService = app(\App\Services\ProjectDepartmentTaskService::class);
    $activeExpand = null;
    foreach ($projectDepartments as $pd) {
        if (in_array($pd['department_status'] ?? '', ['start', 'in_progress', 'delay'])) {
            $activeExpand = $pd['id'];
            break;
        }
    }
    $suggestedProjectCode = $data['suggested_project_code'] ?? '';
    $projectCodeValue = (is_array($project) && trim((string) ($project['project_code'] ?? '')) !== '')
        ? $project['project_code']
        : $suggestedProjectCode;
@endphp

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
               <div class="d-flex justify-content-between align-items-center mb-3">

    <h4 class="page-heading mb-0">
        <span class="page-heading-icon">
            <i class="ri-building-2-line"></i>
        </span>
        {{ $pageTitle }}
    </h4>

    <a href="{{ getProjectsListingUrl() }}" class="back-project-btn">
        <i class="ri-arrow-left-line"></i>
        Back to {{ hasFullProjectsPermission() ? 'Projects' : 'My Projects' }}
    </a>

</div>

                @if($isProjectReadOnly)
                <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
                    <i class="ri-lock-line fs-5"></i>
                    <span>This project is <strong>completed</strong> and is locked for editing. You can review details only.</span>
                </div>
                @endif

                <input type="hidden" id="hdnForeignKeyId" value="{{ $projectId }}">
                <a id="commonActionButton" href="{{ getProjectsListingUrl() }}" style="display:none"></a>

                <div id="basic-example" role="application" class="wizard clearfix">
                    <div class="steps clearfix">
                        <ul role="tablist">
                            <li role="tab" class="first customWizard customWizard-0 @if($initialStep === 0) current @else disabled @endif"
                                @if($enableClick) onclick="enableDisableSections(0)" @endif>
                                <a class="customWizardSteps"><span class="number"></span> General</a>
                            </li>
                            <li role="tab" class="customWizard customWizard-1 @if($initialStep === 1) current @else disabled @endif"
                                @if($canOpenDepartmentsTab) onclick="enableDisableSections(1)" @endif>
                                <a class="customWizardSteps"><span class="number"></span> Departments</a>
                            </li>
                            <li role="tab" class="customWizard customWizard-2 @if($initialStep === 2) current @else disabled @endif"
                                @if($canOpenExecutionTab) onclick="enableDisableSections(2)" @endif>
                                <a class="customWizardSteps"><span class="number"></span> Execution</a>
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
                                <label for="wizard_project_code" class="required-label">Project ID</label>
                                <input type="text" class="form-control required" name="project_code" id="wizard_project_code"
                                    value="{{ $projectCodeValue }}" placeholder="Auto-generated — edit if needed"
                                    @if($isProjectReadOnly) readonly @endif>
                                @if(!$projectId)
                                <small class="text-muted">Auto-generated. You can change it before saving.</small>
                                @endif
                            </div>
                            <div class="col-md-8 mb-2">
                                <label class="required-label">Project Name</label>
                                <input type="text" class="form-control required" name="project_name" value="{{ $project['project_name'] ?? '' }}">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Hospital Name</label>
                                <select name="hospital_id" id="wizard_hospital_id" class="form-control dd-select">
                                    <option value="">Select hospital</option>
                                    @foreach($hospitals as $hospital)
                                    <option value="{{ $hospital['id'] }}" @if($selectedHospitalId == $hospital['id']) selected @endif>{{ $hospital['label'] }}</option>
                                    @endforeach
                                </select>
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
                                <select name="zone_id" id="wizard_zone_id" class="form-control dd-select">
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
                                <select name="location_id" id="wizard_location_id" class="form-control dd-select" data-selected="{{ $selectedLocationId }}">
                                    <option value="">Select location</option>
                                </select>
                                <input type="hidden" name="location" id="wizard_location_text" value="{{ $project['location'] ?? '' }}">
                            </div>
                            <div class="col-md-4 mb-2">
                                @include('project_wizard.partials.project-spoc-user-field', [
                                    'project' => $project ?? [],
                                    'projectSpocUsers' => $projectSpocUsers,
                                ])
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Contractor</label>
                                <input type="text" class="form-control" name="contractor_name" value="{{ $project['contractor_name'] ?? '' }}">
                            </div>
                            @php
                                $wizardPlannedStart = !empty($project['planned_start_date']) ? date('Y-m-d', strtotime($project['planned_start_date'])) : '';
                                $wizardPlannedEnd = !empty($project['planned_completion_date']) ? date('Y-m-d', strtotime($project['planned_completion_date'])) : '';
                            @endphp
                            <div class="col-md-8 mb-2">
                                <div class="row g-2 planned-date-range">
                                    <div class="col-md-6">
                                        <label>Planned Start</label>
                                        <input type="text" class="form-control planned-date-input js-planned-start" name="planned_start_date" autocomplete="off" placeholder="yyyy-mm-dd"
                                            value="{{ $wizardPlannedStart }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label>Planned Completion</label>
                                        <input type="text" class="form-control planned-date-input js-planned-end" name="planned_completion_date" data-label="Planned completion" autocomplete="off" placeholder="yyyy-mm-dd"
                                            value="{{ $wizardPlannedEnd }}" @if($wizardPlannedStart === '') readonly @endif>
                                    </div>
                                </div>
                            </div>
<!--
                            <div class="col-md-4 mb-2">
                                <label>Target Revised Completion</label>
                                <input type="date" class="form-control" name="target_revised_completion_date" value="{{ !empty($project['target_revised_completion_date']) ? date('Y-m-d', strtotime($project['target_revised_completion_date'])) : '' }}">
                            </div>

-->

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
                        <h5 class="mb-3">Select, Order &amp; Configure Departments</h5>
                        <p class="text-muted small mb-3">Choose departments, drag to set execution order, then use <strong>Configure</strong> on each row to assign the department SPOC, planned dates, and parallel workflow before execution.</p>
                        <div class="row">
                            <div class="col-md-5">
                                <label class="form-label small text-muted text-uppercase fw-semibold">Available departments</label>
                                <div class="border rounded p-3 dept-pick-list" style="max-height:480px;overflow-y:auto;">
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
                                <label class="form-label small text-muted text-uppercase fw-semibold">Execution order &amp; setup</label>
                                <div class="dept-sortable-legend mb-2">
                                    <span class="dept-legend-item"><span class="dept-legend-bar dept-legend-bar-missing"></span> SPOC not assigned</span>
                                    <span class="dept-legend-item"><span class="dept-legend-bar dept-legend-bar-assigned"></span> SPOC assigned</span>
                                    <span class="dept-legend-item"><i class="ri-arrow-right-line"></i> Sequential</span>
                                    <span class="dept-legend-item"><i class="ri-git-branch-line"></i> Parallel</span>
                                </div>
                                <ul id="deptSortable" class="list-group dept-sortable mb-0">
                                    @foreach($projectDepartments as $pdIndex => $pd)
                                    @include('project_wizard.partials.dept-sortable-item', [
                                        'pd' => $pd,
                                        'isLast' => ($pdIndex === count($projectDepartments) - 1),
                                        'isProjectReadOnly' => $isProjectReadOnly,
                                        'taskCount' => count($departmentTasksByPd[$pd['id']] ?? []),
                                    ])
                                    @endforeach
                                </ul>
                                <p class="text-muted small mt-2 mb-0" id="deptSortableEmpty" @if(count($projectDepartments)) style="display:none" @endif>Select departments from the left, then configure each row.</p>
                            </div>
                        </div>
                        <input type="hidden" name="department_order" id="department_order" value="">
                        <input type="hidden" name="department_parallel" id="department_parallel" value="">
                        <input type="hidden" name="department_spocs" id="department_spocs" value="">
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
                        <div class="dept-sortable-legend mb-3">
                            <span class="dept-legend-item"><span class="dept-legend-bar dept-legend-bar-missing"></span> SPOC not assigned</span>
                            <span class="dept-legend-item"><span class="dept-legend-bar dept-legend-bar-assigned"></span> SPOC assigned</span>
                            <span class="dept-legend-item"><i class="ri-arrow-right-line"></i> Sequential</span>
                            <span class="dept-legend-item"><i class="ri-git-branch-line"></i> Parallel</span>
                        </div>
                        @php
                            $projectPlannedStartYmd = !empty($project['planned_start_date']) ? date('Y-m-d', strtotime($project['planned_start_date'])) : '';
                        @endphp
                        <div class="accordion" id="deptExecutionAccordion">
                            @foreach($projectDepartments as $index => $pd)
                            @php
                                $status = $pd['department_status'] ?? 'pending';
                                $totalDepts = count($projectDepartments);
                                $isLastDept = ($index === $totalDepts - 1);
                                $allowParallelNext = !empty($pd['allow_parallel_next']);
                                $spocName = trim($pd['spoc_name'] ?? '');
                                $spocBorderClass = $spocName !== '' ? 'dept-has-spoc' : 'dept-spoc-missing';
                                $spocMissing = $spocName === '' && empty($pd['spoc_user_id']);
                                $previousPd = $index > 0 ? $projectDepartments[$index - 1] : null;
                                $prevAllowsParallel = $previousPd && !empty($previousPd['allow_parallel_next']);
                                $sequentialEnforced = $previousPd && !$prevAllowsParallel;
                                $sequentialMinStart = '';
                                $sequentialPrevName = $previousPd['department_name'] ?? '';
                                if ($sequentialEnforced && $previousPd && !empty($previousPd['planned_end_date'])) {
                                    $sequentialMinStart = date('Y-m-d', strtotime($previousPd['planned_end_date']));
                                }
                                $isLocked = $projectDeptService->isDepartmentLocked($pd);
                                $isPending = $isLocked;
                                $actionsDisabled = $isPending || $spocMissing;
                                $expanded = ($activeExpand && $activeExpand == $pd['id']) || (!$activeExpand && $index === 0 && !$isLocked);
                                $encPdId = Crypt::encrypt($pd['id']);
                                $badgeClass = ['pending'=>'secondary','start'=>'info','in_progress'=>'primary','delay'=>'warning','completed'=>'success'][$status] ?? 'secondary';
                                $deptTaskCount = count($departmentTasksByPd[$pd['id']] ?? []);
                            @endphp
                            <div class="accordion-item dept-accordion-item {{ $spocBorderClass }} {{ $status }} mb-2 @if($isLocked) dept-accordion-locked @endif">
                                <h2 class="accordion-header">
                                    <button class="accordion-button @if(!$expanded) collapsed @endif @if($isLocked) disabled @endif" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#collapse_{{ $pd['id'] }}"
                                        @if($isLocked) disabled @endif>
                                        <span class="me-2">{{ $pd['sort_order'] }}.</span>
                                        <strong>{{ $pd['department_name'] }}</strong>
                                        <span class="dept-meta-spoc ms-2">
                                            <i class="{{ $spocName !== '' ? 'ri-user-follow-line dept-icon-spoc-assigned' : 'ri-user-unfollow-line dept-icon-spoc-missing' }}"></i>
                                            <span class="dept-meta-spoc-text">{{ $spocName !== '' ? $spocName : 'SPOC not assigned' }}</span>
                                        </span>
                                        <span class="badge bg-{{ $badgeClass }} ms-2">{{ $statusLabels[$status] ?? ucfirst($status) }}</span>
                                        @include('project_wizard.partials.dept-task-count-badge', [
                                            'projectDepartmentId' => $pd['id'],
                                            'taskCount' => $deptTaskCount,
                                            'cssClass' => 'ms-2',
                                        ])
                                        @if(!$isLastDept)
                                        <span class="dept-meta-parallel dept-meta-flow badge rounded-pill ms-2 {{ $allowParallelNext ? 'bg-info-subtle text-info dept-flow-parallel' : 'bg-secondary-subtle text-secondary dept-flow-sequential' }}">
                                            <i class="{{ $allowParallelNext ? 'ri-git-branch-line' : 'ri-arrow-right-line' }}"></i>
                                            <span>{{ $allowParallelNext ? 'Parallel' : 'Sequential' }}</span>
                                        </span>
                                        @endif
                                        @if($spocMissing)
                                        <span class="badge bg-warning-subtle text-warning ms-2">SPOC required</span>
                                        @endif
                                        @if($isLocked && $status === 'pending')
                                        <span class="badge bg-light text-muted ms-2">Waiting for previous step</span>
                                        @endif
                                    </button>
                                </h2>
                                <div id="collapse_{{ $pd['id'] }}" class="accordion-collapse collapse @if($expanded) show @endif" data-bs-parent="#deptExecutionAccordion">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <p class="text-muted small">{{ $pd['department_description'] ?? '' }}</p>
                                                @include('project_wizard.partials.dept-spoc-required-notice', ['spocMissing' => $spocMissing])
                                                @php
                                                    $hasPlannedDates = !empty($pd['planned_start_date']) || !empty($pd['planned_end_date']);
                                                @endphp
                                                @if(!$isPending)
                                                @include('project_wizard.partials.dept-workflow-fields', [
                                                    'pd' => $pd,
                                                    'status' => $status,
                                                    'showSpoc' => false,
                                                    'spocUsers' => $spocUsers,
                                                    'sequentialEnforced' => $sequentialEnforced,
                                                    'sequentialMinStart' => $sequentialMinStart,
                                                    'sequentialPrevName' => $sequentialPrevName,
                                                    'projectPlannedStart' => $projectPlannedStartYmd,
                                                    'actionsDisabled' => $actionsDisabled,
                                                ])
                                                @elseif($hasPlannedDates)
                                                <div class="alert alert-light border small mb-2 py-2">
                                                    <i class="ri-calendar-line me-1"></i>
                                                    Planned:
                                                    <strong>{{ !empty($pd['planned_start_date']) ? date('Y-m-d', strtotime($pd['planned_start_date'])) : '—' }}</strong>
                                                    →
                                                    <strong>{{ !empty($pd['planned_end_date']) ? date('Y-m-d', strtotime($pd['planned_end_date'])) : '—' }}</strong>
                                                    <span class="text-muted d-block mt-1 mb-0">Dates from department setup. Editable once this step is active.</span>
                                                </div>
                                                @endif
                                            </div>
                                            <div class="col-md-4">
                                                @include('project_wizard.partials.dept-panel-actions', [
                                                    'pd' => $pd,
                                                    'encPdId' => $encPdId,
                                                    'isPending' => $isPending,
                                                    'actionsDisabled' => $actionsDisabled,
                                                ])
                                            </div>
                                        </div>
                                        @include('project_wizard.partials.dept-tasks-section', [
                                            'projectDepartmentId' => (int) $pd['id'],
                                            'projectId' => (int) $projectId,
                                            'tasks' => $departmentTasksByPd[$pd['id']] ?? [],
                                            'linkableDepartments' => $projectDeptTaskService->getLinkableMasterDepartments($projectId, (int) $pd['department_id']),
                                            'taskStatusLabels' => $taskStatusLabels,
                                            'readOnly' => $isProjectReadOnly || $isPending || $spocMissing,
                                            'mode' => 'execution',
                                            'projectPlannedStart' => $projectPlannedStartYmd,
                                        ])
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
                                    <li onclick="calculateSteps(this,'finish',2,'{{ getProjectsListingUrl() }}')"><a href="#finish">Finish</a></li>
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
/* ==========================================
   MODERN DIRECTION WIZARD
========================================== */

#basic-example.wizard{
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:16px;
    padding:20px;
    margin-bottom:25px;
}

#basic-example .steps > ul{
    display:flex;
    gap:15px;
    list-style:none;
    padding:0;
    margin:0;
}

#basic-example .steps > ul > li{
    flex:1;
    position:relative;
}

/* Remove old line */

#basic-example .steps > ul > li::after{
    display:none !important;
}

/* Step Card */

#basic-example .steps > ul > li a.customWizardSteps{
    position:relative;

    display:flex;
    align-items:center;
    gap:12px;

    padding:16px 18px;

    background:#f8fafc;

    border:1px solid #e2e8f0;

    border-radius:14px;

    text-decoration:none;

    color:#64748b;

    font-weight:600;

    min-height:72px;

    transition:.3s;
}

/* Arrow Direction */

#basic-example .steps > ul > li:not(:last-child) a.customWizardSteps::after{
    content:"➜";

    position:absolute;

    right:-22px;
    top:50%;

    transform:translateY(-50%);

    width:32px;
    height:32px;

    display:flex;
    align-items:center;
    justify-content:center;

    border-radius:50%;

    background:#fff;

    border:1px solid #e2e8f0;

    color:#94a3b8;

    z-index:10;
}

/* Number */

#basic-example .steps .number{
    width:42px;
    height:42px;

    min-width:42px;

    display:flex;
    align-items:center;
    justify-content:center;

    border-radius:12px;

    background:#fff;

    border:1px solid #dbeafe;

    color:#64748b;

    font-size:20px;
    font-weight:700;

    margin:0;
}

/* Hover */

#basic-example .steps > ul > li:hover a.customWizardSteps{
    transform:translateY(-2px);

    border-color:#93c5fd;

    background:#f8fbff;
}

/* Current */

#basic-example .steps > ul > li.current a.customWizardSteps{

    background:linear-gradient(
        135deg,
        #1F8EF1,
        #00A99D
    );

    border:none;

    color:#fff;

    box-shadow:
        0 10px 25px rgba(31,142,241,.20);
}

#basic-example .steps > ul > li.current .number{

    background:rgba(255,255,255,.18);

    border:none;

    color:#fff;
}

/* Completed */

#basic-example .steps > ul > li.stepsCompleted a.customWizardSteps{

    background:#ecfdf5;

    border:1px solid #10b981;

    color:#047857;
}

#basic-example .steps > ul > li.stepsCompleted .number{

    background:#10b981;

    color:#fff;

    border:none;
}

/* Completed Arrow */

#basic-example .steps > ul > li.stepsCompleted a.customWizardSteps::after{
    background:#10b981;
    color:#fff;
    border:none;
}

/* Disabled */

#basic-example .steps > ul > li.disabled .number{
    background:#fff;
    color:#94a3b8;
    border-color:#e2e8f0;
}

#basic-example .steps > ul > li.disabled a{
    color:#94a3b8;
}

/* Mobile */

@media(max-width:768px){

    #basic-example .steps > ul{
        flex-direction:column;
    }

    #basic-example .steps > ul > li{
        width:100%;
    }

    #basic-example .steps > ul > li a.customWizardSteps::after{
        display:none;
    }
}
/* icon css */
/* General */
.customWizard-0 .number::before{
    content:"📋";
    margin-right:4px;
}

/* Departments */
.customWizard-1 .number::before{
    content:"🏢";
    margin-right:4px;
}

/* Execution */
.customWizard-2 .number::before{
    content:"⚙️";
    margin-right:4px;
}
/* End icon */

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
/* ===================================
   FORM SECTION
=================================== */

.commonSections{
    background:#fff;

    border:1px solid #e2e8f0;

    border-radius:18px;

    padding:25px;

    margin-bottom:20px;
}

/* Heading */

.commonSections h5{
    position:relative;

    margin-bottom:25px;

    padding-bottom:15px;

    font-size:17px;

    font-weight:700;

    color:#1e293b;

    border-bottom:1px solid #e2e8f0;
}

.commonSections h5::before{
    content:"";

    position:absolute;

    left:0;
    bottom:-1px;

    width:70px;
    height:3px;

    border-radius:10px;

    background:linear-gradient(
        90deg,
        #1F8EF1,
        #00A99D
    );
}

/* Labels */

.commonSections label{
    font-size:13px;

    font-weight:600;

    color:#475569;

    margin-bottom:7px;

    display:block;
}

/* Required Label */

.required-label::after{
    content:" *";

    color:#ef4444;
}

/* Inputs */

.commonSections .form-control,
.commonSections .dd-select{

    height:48px;

    border:1px solid #dbe2ea;

    border-radius:12px;

    background:#fff;

    font-size:14px;

    transition:.3s ease;
}

.commonSections textarea.form-control{
    height:auto;
    min-height:110px;
}

/* Focus */

.commonSections .form-control:focus,
.commonSections .dd-select:focus{

    border-color:#1F8EF1;

    box-shadow:
        0 0 0 4px rgba(31,142,241,.08);

    outline:none;
}

/* Row Gap */

.commonSections .row{
    row-gap:10px;
}

/* Input Hover */

.commonSections .form-control:hover,
.commonSections .dd-select:hover{
    border-color:#93c5fd;
}

/* Footer Buttons */

.commonSections .wizard .actions{
    margin-top:25px;

    border-top:1px solid #e2e8f0;

    padding-top:20px;
}

.commonSections .wizard .actions ul{
    display:flex;

    justify-content:flex-end;

    gap:10px;

    padding:0;
    margin:0;

    list-style:none;
}

.commonSections .wizard .actions a{

    min-width:120px;

    height:46px;

    display:flex;
    align-items:center;
    justify-content:center;

    border-radius:12px;

    text-decoration:none;

    font-weight:600;

    transition:.3s ease;
}

/* Previous */

.commonSections .wizard .actions li:first-child a{

    background:#f8fafc;

    color:#64748b !important;

    border:1px solid #dbe2ea;
}

/* Next */

.commonSections .wizard .actions li:last-child a{

    background:linear-gradient(
        135deg,
        #1F8EF1,
        #00A99D
    );

    color:#fff !important;

    border:none;

    box-shadow:
        0 8px 20px rgba(31,142,241,.18);
}

.commonSections .wizard .actions li:last-child a:hover{

    transform:translateY(-2px);

    box-shadow:
        0 12px 25px rgba(31,142,241,.25);
}

/* Disabled */

.commonSections .wizard .actions .disabled a{

    background:#f1f5f9;

    color:#94a3b8 !important;

    border:1px solid #e2e8f0;
}

/* Mobile */

@media(max-width:768px){

    .commonSections{
        padding:18px;
    }

    .commonSections h5{
        font-size:18px;
    }

    .commonSections .wizard .actions ul{
        flex-direction:column;
    }

    .commonSections .wizard .actions a{
        width:100%;
    }
}
/* ======================================
   DEPARTMENT SORTABLE (STEP 2)
====================================== */

.dept-sortable .list-group-item{
    border-radius:12px;
    margin-bottom:10px;
    border:1px solid #e2e8f0;
    border-left-width:4px;
    border-left-color:#e2e8f0;
    padding:14px 16px;
}

.dept-sortable-item{
    display:flex;
    align-items:center;
    gap:12px;
    background:#fff;
    transition:.2s ease;
}

.dept-sortable-item.dept-spoc-missing,
.dept-accordion-item.dept-spoc-missing{
    border-left-color:#f59e0b !important;
}

.dept-sortable-item.dept-has-spoc,
.dept-accordion-item.dept-has-spoc{
    border-left-color:#22c55e !important;
}

.dept-sortable-item.dept-spoc-missing{
    background:linear-gradient(90deg, #fffbeb 0%, #fff 28%);
}

.dept-sortable-item.dept-has-spoc{
    background:linear-gradient(90deg, #f0fdf4 0%, #fff 22%);
}

.dept-sortable-item.dept-spoc-missing:hover,
.dept-accordion-item.dept-spoc-missing:hover{
    border-color:#fcd34d;
    box-shadow:0 4px 14px rgba(245,158,11,.12);
}

.dept-sortable-item.dept-has-spoc:hover,
.dept-accordion-item.dept-has-spoc:hover{
    border-color:#86efac;
    box-shadow:0 4px 14px rgba(34,197,94,.12);
}

.planned-end-locked{
    background-color:#f1f5f9;
    cursor:not-allowed;
}

.dept-sortable-legend{
    display:flex;
    flex-wrap:wrap;
    align-items:center;
    gap:12px 18px;
    font-size:12px;
    color:#64748b;
    padding:8px 12px;
    background:#f8fafc;
    border:1px dashed #e2e8f0;
    border-radius:10px;
}

.dept-legend-item{
    display:inline-flex;
    align-items:center;
    gap:6px;
}

.dept-legend-bar{
    display:inline-block;
    width:4px;
    height:16px;
    border-radius:4px;
}

.dept-legend-bar-missing{
    background:#f59e0b;
}

.dept-legend-bar-assigned{
    background:#22c55e;
}

.dept-icon-spoc-missing{
    color:#d97706;
}

.dept-icon-spoc-assigned{
    color:#16a34a;
}

.dept-meta-flow{
    display:inline-flex;
    align-items:center;
    gap:5px;
    font-weight:500;
    font-size:11px;
    padding:4px 10px;
}

.dept-meta-flow i{
    font-size:14px;
    line-height:1;
}

.dept-flow-parallel i{
    color:#0284c7;
}

.dept-flow-sequential i{
    color:#64748b;
}

.dept-sortable-item:hover{
    box-shadow:0 4px 14px rgba(37,99,235,.08);
}

.dept-sortable-main{
    display:flex;
    align-items:flex-start;
    gap:12px;
    min-width:0;
}

.dept-sortable-drag{
    color:#94a3b8;
    font-size:18px;
    line-height:1;
    padding-top:2px;
    cursor:grab;
}

.dept-sortable-title{
    font-weight:600;
    color:#1e293b;
    font-size:15px;
}

.dept-sortable-meta{
    display:flex;
    flex-wrap:wrap;
    align-items:center;
    gap:8px;
    margin-top:6px;
}

.dept-meta-spoc{
    display:inline-flex;
    align-items:center;
    gap:4px;
    font-size:12px;
    color:#64748b;
}

.dept-meta-spoc-text{
    max-width:220px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

.dept-sortable-actions{
    display:flex;
    align-items:center;
    gap:4px;
    flex-shrink:0;
}

.dept-pick-list .form-check{
    padding:8px 10px;
    border-radius:10px;
    transition:.15s ease;
}

.dept-pick-list .form-check:hover{
    background:#f8fafc;
}

.dept-accordion-item.dept-accordion-locked .accordion-button.disabled{
    opacity:.72;
    cursor:not-allowed;
}

/* ======================================
   MODERN DEPARTMENT ACCORDION
====================================== */

.dept-accordion-item{
    border:none !important;

    border-radius:16px !important;

    overflow:hidden;

    background:#fff;

    margin-bottom:16px !important;

    box-shadow:
        0 2px 12px rgba(15,23,42,.05);

    transition:.3s ease;
}

.dept-accordion-item:hover{
    transform:translateY(-2px);

    box-shadow:
        0 10px 25px rgba(15,23,42,.08);
}

/* Header */

.dept-accordion-item .accordion-button{

    background:#fff;

    padding:18px 22px;

    border:none;

    box-shadow:none;

    font-size:15px;

    font-weight:600;

    color:#1e293b;
}

.dept-accordion-item .accordion-button:not(.collapsed){

    background:#f8fbff;

    color:#0f172a;

    box-shadow:none;
}

/* Number */

.dept-accordion-item .accordion-button span:first-child{

    width:34px;
    height:34px;

    border-radius:10px;

    display:flex;
    align-items:center;
    justify-content:center;

    background:#eff6ff;

    color:#2563eb;

    font-size:13px;
    font-weight:700;
}

/* Arrow */

.accordion-button::after{
    background-size:16px;
}

/* Body */

.dept-accordion-item .accordion-body{

    padding:22px;

    border-top:1px solid #eef2f7;

    background:#fff;
}

/* Description */

.dept-accordion-item .text-muted.small{

    background:#f8fafc;

    padding:12px 15px;

    border-radius:10px;

    border-left:3px solid #2563eb;

    margin-bottom:18px;
}

.dept-accordion-item .accordion-button .dept-meta-flow{
    margin-left:.35rem;
}

.dept-accordion-item .accordion-button .dept-task-count-badge{
    flex-shrink:0;
    font-weight:500;
}

.dept-accordion-item .accordion-button .dept-task-count-badge i{
    margin-right:.15rem;
    vertical-align:-1px;
}

.dept-task-item{
    flex-wrap:wrap;
}

.dept-task-status-quick{
    flex-shrink:0;
}

.dept-task-status-quick .btn{
    font-size:11px;
    padding:.2rem .45rem;
}

@media (max-width: 768px){
    .dept-task-status-quick{
        width:100%;
        order:3;
        margin-top:.35rem;
    }
    .dept-task-item-actions{
        margin-left:auto;
    }
}

.dept-spoc-required-alert{
    border-left:4px solid #f59e0b;
    background:#fffbeb;
    border-radius:10px;
}

.dept-actions-disabled .form-control,
.dept-actions-disabled .btn{
    pointer-events:none;
}

.dept-panel-actions .wizard-panel-btn:disabled{
    opacity:.55;
    cursor:not-allowed;
    pointer-events:none;
}

.sidelayout-offcanvas .datepicker,
.offcanvas .datepicker{
    z-index:1090 !important;
}

/* Execution accordions — same SPOC left-border treatment as step 2 sortable */
.dept-accordion-item.dept-spoc-missing,
.dept-accordion-item.dept-has-spoc{
    border:1px solid #e2e8f0 !important;
    border-left-width:4px !important;
}

.dept-accordion-item.dept-spoc-missing{
    background:linear-gradient(90deg, #fffbeb 0%, #fff 28%) !important;
}

.dept-accordion-item.dept-has-spoc{
    background:linear-gradient(90deg, #f0fdf4 0%, #fff 22%) !important;
}

.dept-accordion-item .accordion-button .dept-meta-spoc{
    display:inline-flex;
    align-items:center;
    gap:4px;
    font-size:12px;
    font-weight:500;
    color:#64748b;
}

.dept-accordion-item .accordion-button .dept-meta-spoc-text{
    max-width:180px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

/* Status Badges */

.badge{
    border-radius:30px !important;

    padding:7px 12px !important;

    font-size:11px !important;

    font-weight:600 !important;
}

/* Action Buttons */

.btn-group-sm .btn{

    border-radius:10px !important;

    padding:8px 15px;

    font-weight:600;

    margin-right:8px;
}

/* Right Action Panel */

.dept-panel-actions{

    background:#f8fafc;

    border:1px solid #e2e8f0;

    border-radius:16px;

    padding:18px;
}

.dept-panel-actions-label{

    font-size:11px;

    text-transform:uppercase;

    letter-spacing:1px;

    color:#64748b;

    font-weight:700;

    margin-bottom:15px;
}

/* Cards */

.dept-panel-card{

    display:flex;

    align-items:center;

    gap:14px;

    width:100%;

    padding:14px;
    margin-bottom:12px;
    border:none;
    border-radius:14px;
    background:#fff;
text-align: left;
    transition:.3s ease;

    box-shadow:
        0 2px 10px rgba(15,23,42,.04);
}

.dept-panel-card:last-child{
    margin-bottom:0;
}

.dept-panel-card:hover:not(:disabled){

    transform:translateX(5px);

    box-shadow:
        0 8px 20px rgba(15,23,42,.08);
}

/* Icons */

.dept-panel-icon{

    width:48px;
    height:48px;

    border-radius:14px;

    display:flex;
    align-items:center;
    justify-content:center;

    font-size:20px;
}

/* Delay */

.dept-panel-delay .dept-panel-icon{

    background:rgba(245,158,11,.12);

    color:#f59e0b;
}

/* Financial */

.dept-panel-financial .dept-panel-icon{

    background:rgba(14,165,233,.12);

    color:#0ea5e9;
}

/* Attachments */

.dept-panel-attachments .dept-panel-icon{

    background:rgba(99,102,241,.12);

    color:#6366f1;
}

/* Text */

.dept-panel-text strong{

    display:block;

    color:#0f172a;

    font-size:14px;

    font-weight:700;
}

.dept-panel-text small{

    color:#64748b;

    font-size:12px;
}

/* Arrow */

.dept-panel-arrow{

    margin-left:auto;

    font-size:22px;

    color:#94a3b8;

    transition:.3s;
}

.dept-panel-card:hover .dept-panel-arrow{

    color:#2563eb;

    transform:translateX(4px);
}

/* Disabled */

.dept-panel-card:disabled{

    opacity:.55;

    cursor:not-allowed;

    background:#f1f5f9;
}

/* Responsive */

@media(max-width:991px){

    .dept-panel-actions{
        margin-top:20px;
    }

    .dept-accordion-item .accordion-button{
        padding:15px;
    }

    .dept-accordion-item .accordion-body{
        padding:15px;
    }
}

@media(max-width:576px){

    .dept-panel-card{
        padding:12px;
    }

    .dept-panel-icon{
        width:42px;
        height:42px;
        font-size:18px;
    }

    .dept-panel-text strong{
        font-size:13px;
    }

    .dept-panel-text small{
        font-size:11px;
    }
}
.page-heading{
    display:flex;
    align-items:center;
    gap:12px;

    font-size:18px;
    font-weight:700;

    color:#1e293b;
}

.page-heading-icon{

    width:36px;
    height:36px;

    display:flex;
    align-items:center;
    justify-content:center;

    border-radius:12px;

    background:linear-gradient(
        135deg,
        #1F8EF1,
        #00A99D
    );

    color:#fff;

    font-size:16px;

    box-shadow:
        0 8px 20px rgba(31,142,241,.20);
}

.back-project-btn{

    display:flex;
    align-items:center;
    gap:6px;

    padding:8px 14px;

    border-radius:10px;

    background:#f8fafc;

    border:1px solid #e2e8f0;

    color:#64748b;

    text-decoration:none;

    font-size:13px;
    font-weight:600;

    transition:.3s ease;
}

.back-project-btn:hover{

    background:#eff6ff;

    border-color:#bfdbfe;

    color:#2563eb;
}
.btn-outline-primary{
    border-radius: 20px;
}
.dept-has-spoc .btn-outline-primary{
    border-radius: 20px;
}
.dept-has-spoc .btn-outline-primary:hover{
     background:var(--dark-blue);
     border-color:var(--dark-blue);
}
.remove-dept{
    background: #ffffff;
    border-color: #F44336;
    border-radius: 20px;
}
</style>
@endpush

<script>
var projectDeptMaster = @json($masterDepartments);
var spocUserOptions = @json($spocUsers);
var projectSpocUserOptions = @json($projectSpocUsers);
var projectWizardInitialStep = {{ $initialStep }};
var projectWizardEnableClick = {{ $enableClick ? 'true' : 'false' }};
var projectWizardCanOpenDepartments = {{ $canOpenDepartmentsTab ? 'true' : 'false' }};
var projectWizardCanOpenExecution = {{ $canOpenExecutionTab ? 'true' : 'false' }};
var projectWizardReadOnly = {{ $isProjectReadOnly ? 'true' : 'false' }};

function getDeptLiAttr($li, key) {
    return ($li.attr('data-' + key) || '').toString();
}

function setDeptLiAttr($li, key, value) {
    $li.attr('data-' + key, value == null ? '' : String(value));
    $li.removeData(key.replace(/-([a-z])/g, function(m, c) { return c.toUpperCase(); }));
}

function getDeptSequentialConstraint(sortIndex) {
    var idx = parseInt(sortIndex, 10);
    if (isNaN(idx) || idx <= 0) {
        return null;
    }
    var $prev = $('#deptSortable li').eq(idx - 1);
    if (!$prev.length) {
        return null;
    }
    if (parseInt(getDeptLiAttr($prev, 'allow-parallel'), 10) === 1) {
        return null;
    }
    var prevEnd = getDeptLiAttr($prev, 'planned-end').trim();
    if (!prevEnd) {
        return null;
    }
    return {
        minStart: prevEnd,
        prevName: $prev.find('.dept-sortable-title').text().trim() || 'previous department'
    };
}

function deptFlowBadgeHtml(allowParallel) {
    if (allowParallel) {
        return '<span class="dept-meta-parallel dept-meta-flow badge rounded-pill bg-info-subtle text-info dept-flow-parallel">' +
            '<i class="ri-git-branch-line" title="Next department may start in parallel"></i><span>Parallel</span></span>';
    }
    return '<span class="dept-meta-parallel dept-meta-flow badge rounded-pill bg-secondary-subtle text-secondary dept-flow-sequential">' +
        '<i class="ri-arrow-right-line" title="Next department waits for completion"></i><span>Sequential</span></span>';
}

function deptTaskCountBadgeHtml(projectDepartmentId, taskCount, cssClass) {
    if (typeof ProjectDepartmentTasks !== 'undefined' && typeof ProjectDepartmentTasks.taskCountBadgeHtml === 'function') {
        return ProjectDepartmentTasks.taskCountBadgeHtml(projectDepartmentId, taskCount, cssClass);
    }
    projectDepartmentId = parseInt(projectDepartmentId, 10) || 0;
    taskCount = parseInt(taskCount, 10) || 0;
    cssClass = (cssClass || '').trim();
    var tone = taskCount > 0 ? 'bg-primary-subtle text-primary' : 'bg-light text-muted';
    return '<span class="badge rounded-pill dept-task-count-badge ' + tone +
        (cssClass ? ' ' + cssClass : '') + '" data-pd-id="' + projectDepartmentId + '" title="Tasks configured">' +
        '<i class="ri-list-check-2"></i><span class="dept-task-count-label">' + taskCount + ' ' +
        (taskCount === 1 ? 'task' : 'tasks') + '</span></span>';
}

function refreshDeptSortableMeta($li) {
    var spocName = getDeptLiAttr($li, 'spoc-name').trim();
    var allowParallel = parseInt(getDeptLiAttr($li, 'allow-parallel'), 10) === 1;
    var isLast = $li.is('#deptSortable li:last');
    var plannedStart = getDeptLiAttr($li, 'planned-start').trim();
    var plannedEnd = getDeptLiAttr($li, 'planned-end').trim();

    $li.toggleClass('dept-has-spoc', spocName !== '');
    $li.toggleClass('dept-spoc-missing', spocName === '');

    $li.find('.dept-meta-spoc-text').text(spocName !== '' ? spocName : 'SPOC not assigned');
    $li.find('.dept-meta-spoc > i')
        .attr('class', spocName !== ''
            ? 'ri-user-follow-line dept-icon-spoc-assigned'
            : 'ri-user-unfollow-line dept-icon-spoc-missing');

    var $dates = $li.find('.dept-meta-dates');
    if (plannedStart || plannedEnd) {
        var dateLabel = (plannedStart || '—') + ' → ' + (plannedEnd || '—');
        if (!$dates.length) {
            $li.find('.dept-sortable-meta').append('<span class="dept-meta-dates text-muted small ms-2"><i class="ri-calendar-line"></i> <span class="dept-meta-dates-text"></span></span>');
            $dates = $li.find('.dept-meta-dates');
        }
        $dates.find('.dept-meta-dates-text').text(dateLabel);
        $dates.show();
    } else if ($dates.length) {
        $dates.hide();
    }

    var $badge = $li.find('.dept-meta-parallel');
    if (isLast) {
        $badge.remove();
    } else if (!$badge.length) {
        $li.find('.dept-sortable-meta').append(deptFlowBadgeHtml(allowParallel));
        $badge = $li.find('.dept-meta-parallel');
    } else {
        $badge.replaceWith(deptFlowBadgeHtml(allowParallel));
    }

    var pdId = parseInt(getDeptLiAttr($li, 'pd-id'), 10) || 0;
    var $taskBadge = $li.find('.dept-task-count-badge');
    if (!$taskBadge.length) {
        $li.find('.dept-sortable-meta').append(deptTaskCountBadgeHtml(pdId, 0, 'ms-2'));
        $taskBadge = $li.find('.dept-task-count-badge');
    }
    $taskBadge.attr('data-pd-id', pdId);
}

function syncProjectDepartmentOrder() {
    var ids = [];
    var parallel = {};
    var spocs = {};

    $('#deptSortable li').each(function(index) {
        var $li = $(this);
        var id = getDeptLiAttr($li, 'dept-id');
        ids.push(id);

        var isLast = (index === $('#deptSortable li').length - 1);
        var allowParallel = !isLast && parseInt(getDeptLiAttr($li, 'allow-parallel'), 10) === 1;
        parallel[id] = allowParallel ? 1 : 0;

        spocs[id] = {
            spoc_user_id: getDeptLiAttr($li, 'spoc-user-id'),
            spoc_name: getDeptLiAttr($li, 'spoc-name'),
            allow_parallel_next: allowParallel ? 1 : 0,
            planned_start_date: getDeptLiAttr($li, 'planned-start'),
            planned_end_date: getDeptLiAttr($li, 'planned-end')
        };

        refreshDeptSortableMeta($li);
    });

    $('#department_order').val(ids.join(','));
    $('#department_parallel').val(JSON.stringify(parallel));
    $('#department_spocs').val(JSON.stringify(spocs));
    $('#deptSortableEmpty').toggle(ids.length === 0);
}

function buildDeptSortableItem(dept) {
    var deptId = dept.id || dept.department_id;
    var deptName = dept.department_name || dept.label || 'Department';
    var html = '<li class="list-group-item dept-sortable-item dept-spoc-missing" data-dept-id="' + deptId + '" data-pd-id="0" data-spoc-user-id="" data-spoc-name="" data-allow-parallel="0" data-planned-start="" data-planned-end="" data-pd-token="">' +
        '<div class="dept-sortable-main flex-grow-1">' +
        '<div class="dept-sortable-drag"><i class="ri-drag-move-2-line"></i></div>' +
        '<div class="dept-sortable-content">' +
        '<div class="dept-sortable-title">' + deptName + '</div>' +
        '<div class="dept-sortable-meta">' +
        '<span class="dept-meta-spoc"><i class="ri-user-unfollow-line dept-icon-spoc-missing"></i><span class="dept-meta-spoc-text">SPOC not assigned</span></span>' +
        deptFlowBadgeHtml(false) +
        deptTaskCountBadgeHtml(0, 0, 'ms-2') +
        '</div></div></div>' +
        '<div class="dept-sortable-actions">';

    if (!projectWizardReadOnly) {
        html += '<button type="button" class="btn btn-sm btn-outline-primary btn-config-dept" title="Configure SPOC, dates & workflow">' +
            '<i class="ri-settings-3-line me-1"></i> Configure</button>';
    }

    html += '<button type="button" class="btn btn-sm btn-link text-danger remove-dept" title="Remove">&times;</button>' +
        '</div></li>';

    return html;
}

window.updateDeptSortableItem = function(res) {
    var deptId = String(res.department_id || '');
    var $li = $('#deptSortable li').filter(function() {
        return getDeptLiAttr($(this), 'dept-id') === deptId;
    }).first();
    if (!$li.length) {
        return;
    }

    if (res.project_department_id) {
        setDeptLiAttr($li, 'pd-id', res.project_department_id);
    }
    if (res.project_department_token) {
        setDeptLiAttr($li, 'pd-token', res.project_department_token);
    }
    setDeptLiAttr($li, 'spoc-user-id', res.spoc_user_id || '');
    setDeptLiAttr($li, 'spoc-name', res.spoc_name || '');
    setDeptLiAttr($li, 'allow-parallel', parseInt(res.allow_parallel_next, 10) === 1 ? '1' : '0');
    setDeptLiAttr($li, 'planned-start', res.planned_start_date ? String(res.planned_start_date).substring(0, 10) : '');
    setDeptLiAttr($li, 'planned-end', res.planned_end_date ? String(res.planned_end_date).substring(0, 10) : '');

    refreshDeptSortableMeta($li);
    syncProjectDepartmentOrder();
};

function openDeptSetupPanel($li) {
    var deptId = getDeptLiAttr($li, 'dept-id');
    var projectId = $('#projectDeptMasterId').val() || $('#projectMasterId').val();
    if (!projectId) {
        parseFormErrors({ error: 1, msg: ['Save general details first before configuring departments.'] }, 'error');
        return;
    }

    syncProjectDepartmentOrder();

    var pdToken = getDeptLiAttr($li, 'pd-token');
    if (!pdToken) {
        pdToken = 'new';
    }

    var sortIndex = $li.index();
    var total = $('#deptSortable li').length;
    var url = "{{ getProjectUrl('projects/wizard/dept-setup') }}/" + encodeURIComponent(pdToken) +
        '?project_id=' + encodeURIComponent(projectId) +
        '&department_id=' + encodeURIComponent(deptId) +
        '&sort_index=' + sortIndex +
        '&total=' + total;

    var seq = getDeptSequentialConstraint(sortIndex);
    if (seq && seq.minStart) {
        url += '&seq_min_start=' + encodeURIComponent(seq.minStart);
        url += '&seq_prev_name=' + encodeURIComponent(seq.prevName || 'previous department');
    }

    openSideLayout({}, url, 'Configure Department');
}

function reloadWizardExecutionStep() {
    var href = window.location.href.split('#')[0];
    var qIndex = href.indexOf('?');
    var base = qIndex > -1 ? href.substring(0, qIndex) : href;
    var query = qIndex > -1 ? href.substring(qIndex + 1) : '';
    var params = {};

    if (query) {
        query.split('&').forEach(function(part) {
            if (!part) {
                return;
            }
            var pair = part.split('=');
            var key = decodeURIComponent(pair[0] || '');
            if (key) {
                params[key] = decodeURIComponent(pair[1] || '');
            }
        });
    }

    params.step = 'execution';
    params._ = String(Date.now());

    var nextQuery = Object.keys(params).map(function(key) {
        return encodeURIComponent(key) + '=' + encodeURIComponent(params[key]);
    }).join('&');

    window.location.assign(base + '?' + nextQuery);
}

function initProjectExecutionStep() {
    window.location.reload();
}

function bindExecutionPanelHandlers() {
    $('.wizard-panel-btn').off('click').on('click', function() {
        if ($(this).prop('disabled')) return;
        openSideLayout({}, $(this).data('url'), $(this).data('title'));
    });

    bindDepartmentWorkflowHandlers({
        saveUrl: "{{ getProjectUrl('save_project_department') }}",
        statusUrl: "{{ getProjectUrl('update_department_status') }}",
        csrfToken: '{{ csrf_token() }}',
        onSuccess: reloadWizardExecutionStep
    });

    if (typeof bindPlannedDateRangeInputs === 'function') {
        bindPlannedDateRangeInputs($('.section2'));
    }

    if (typeof ProjectDepartmentTasks !== 'undefined') {
        ProjectDepartmentTasks.bind($('.section2'));
    }

    initSpocUserControls();
}

function renderSpocSelectOptions($select, selectedId, users) {
    users = users || spocUserOptions;
    if ($.fn.select2 && $select.hasClass('select2-hidden-accessible')) {
        $select.select2('destroy');
    }
    var placeholder = $select.hasClass('project-spoc-user-select') ? 'Select Project SPOC' : 'Select SPOC user';
    var html = '<option value="">' + placeholder + '</option>';
    (users || []).forEach(function(user) {
        var sel = (String(selectedId) === String(user.id)) ? ' selected' : '';
        html += '<option value="' + user.id + '"' + sel + '>' + user.label + '</option>';
    });
    $select.html(html);
    if ($.fn.select2) {
        $select.select2({ width: '100%', placeholder: placeholder });
    }
}

function refreshAllSpocSelects(selectUserId, $activeBlock, spocRole) {
    var isProject = spocRole === 'project' || ($activeBlock && $activeBlock.hasClass('project-spoc-user-block'));
    var selector = isProject ? '.project-spoc-user-select' : '.spoc-user-select';
    var users = isProject ? projectSpocUserOptions : spocUserOptions;

    $(selector).each(function() {
        var $sel = $(this);
        var isActive = $activeBlock && (
            (isProject && $activeBlock.find('.project-spoc-user-select')[0] === this) ||
            (!isProject && $activeBlock.find('.spoc-user-select')[0] === this)
        );
        var val = (isActive && selectUserId) ? selectUserId : $sel.val();
        renderSpocSelectOptions($sel, val, users);
        $sel.val(val).trigger('change');
    });
}

function initSpocUserControls() {
    if ($.fn.select2) {
        $('.spoc-user-select, .project-spoc-user-select').each(function() {
            if (!$(this).hasClass('select2-hidden-accessible')) {
                var placeholder = $(this).hasClass('project-spoc-user-select') ? 'Select Project SPOC' : 'Select SPOC user';
                $(this).select2({ width: '100%', placeholder: placeholder });
            }
        });
    }

    $('.spoc-user-select').off('change.spoc').on('change.spoc', function() {
        var $opt = $(this).find('option:selected');
        var name = '';
        if ($(this).val()) {
            var label = $opt.text() || '';
            name = label.split(' — ')[0] || label;
        }
        $(this).closest('.spoc-user-block').find('.spoc-name-hidden').val(name);
    });

    $('.project-spoc-user-select').off('change.projectSpoc').on('change.projectSpoc', function() {
        var $opt = $(this).find('option:selected');
        var name = '';
        if ($(this).val()) {
            var label = $opt.text() || '';
            name = label.split(' — ')[0] || label;
        }
        $(this).closest('.project-spoc-user-block').find('.project-spoc-name-hidden').val(name);
    });

    $('.toggle-spoc-add-form').off('click').on('click', function() {
        var $block = $(this).closest('.spoc-user-block, .project-spoc-user-block');
        $block.find('.spoc-add-form').slideToggle(150);
    });

    $('.cancel-spoc-add').off('click').on('click', function() {
        $(this).closest('.spoc-add-form').slideUp(150);
    });

    $('.save-spoc-user').off('click').on('click', function() {
        var $btn = $(this);
        var $block = $(this).closest('.spoc-user-block, .project-spoc-user-block');
        var spocRole = $block.data('spoc-role') || 'department';
        var payload = new FormData();
        payload.append('_token', '{{ csrf_token() }}');
        payload.append('spoc_role', spocRole);
        payload.append('department_id', $block.data('department-id') || '');
        payload.append('project_department_id', $block.data('pd-id') || '');
        payload.append('first_name', $block.find('.spoc-add-first-name').val());
        payload.append('last_name', $block.find('.spoc-add-last-name').val());
        payload.append('email_id', $block.find('.spoc-add-email').val());
        payload.append('mobile_no', $block.find('.spoc-add-mobile').val());
        payload.append('password', $block.find('.spoc-add-password').val());

        ajaxRequestWithPromise("{{ getProjectUrl('wizard_create_spoc_user') }}", payload, 'wizard_create_spoc_user', 1, '', $btn)
            .then(function(res) {
                if (res.error == 0 || res.error == '0') {
                    var users = res.users || [];
                    if (res.spoc_role === 'project') {
                        projectSpocUserOptions = users;
                    } else {
                        spocUserOptions = users;
                    }
                    if (res.user) {
                        var target = res.spoc_role === 'project' ? projectSpocUserOptions : spocUserOptions;
                        target = target || [];
                        var exists = target.some(function(u) { return String(u.id) === String(res.user.id); });
                        if (!exists) {
                            target.push(res.user);
                        }
                        if (res.spoc_role === 'project') {
                            projectSpocUserOptions = target;
                        } else {
                            spocUserOptions = target;
                        }
                    }
                    refreshAllSpocSelects(res.user ? res.user.id : '', $block, res.spoc_role || spocRole);
                    $block.find('.spoc-add-form').slideUp(150);
                    $block.find('.spoc-add-first-name, .spoc-add-last-name, .spoc-add-email, .spoc-add-mobile, .spoc-add-password').val('');
                }
            });
    });
}

$(function() {
    function loadWizardLocations(zoneId, selectedId) {
        var $loc = $('#wizard_location_id');
        $loc.html('<option value="">Select location</option>');
        if (!zoneId) {
            if ($.fn.select2) { $loc.trigger('change.select2'); }
            return;
        }
        $.post("{{ getProjectUrl('get_locations_by_zone') }}", {
            _token: '{{ csrf_token() }}',
            zone_id: zoneId
        }).done(function(res) {
            (res.locations || []).forEach(function(loc) {
                var sel = (String(selectedId) === String(loc.id)) ? ' selected' : '';
                $loc.append('<option value="' + loc.id + '"' + sel + '>' + loc.location_name + '</option>');
            });
            if ($.fn.select2) { $loc.trigger('change.select2'); }
        });
    }

    $('#wizard_zone_id').on('change', function() {
        loadWizardLocations($(this).val(), '');
    });

    $('#wizard_location_id').on('change', function() {
        var text = $(this).find('option:selected').text();
        $('#wizard_location_text').val($(this).val() ? text : '');
    });

    if ($.fn.select2) { $('.dd-select').select2({ width: '100%' }); }

    if (typeof bindPlannedDateRangeInputs === 'function') {
        bindPlannedDateRangeInputs($('#masterForm0'));
        bindPlannedDateRangeInputs($('.section2'));
    }

    var initZone = $('#wizard_zone_id').val();
    var initLoc = $('#wizard_location_id').data('selected') || '';
    if (initZone) {
        loadWizardLocations(initZone, initLoc);
    }

    if (projectWizardEnableClick) {
        $('.customWizard-0').removeClass('disabled');
    }
    if (projectWizardCanOpenDepartments) {
        $('.customWizard-1').removeClass('disabled');
    }
    if (projectWizardCanOpenExecution) {
        $('.customWizard-2').removeClass('disabled');
    }
    enableDisableSections(projectWizardInitialStep);
    $('.customWizard-' + projectWizardInitialStep).addClass('current');

    $('#deptSortable').sortable({
        placeholder: 'list-group-item bg-light dept-sortable-item',
        handle: '.dept-sortable-drag',
        update: syncProjectDepartmentOrder
    });
    syncProjectDepartmentOrder();

    $(document).on('click', '.btn-config-dept', function() {
        if (projectWizardReadOnly) return;
        openDeptSetupPanel($(this).closest('li'));
    });

    $('.dept-pick').on('change', function() {
        var id = $(this).val();
        if ($(this).is(':checked')) {
            if (!$('#deptSortable li[data-dept-id="' + id + '"]').length) {
                var dept = projectDeptMaster.find(function(d) { return String(d.id) === String(id); });
                if (dept) {
                    $('#deptSortable').append(buildDeptSortableItem(dept));
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

    if (projectWizardReadOnly) {
        $('.masterForm').find('input, select, textarea, button').prop('disabled', true);
        $('.wizard .actions').hide();
        $('.toggle-spoc-add-form, .save-spoc-user, .remove-dept, .btn-config-dept').hide();
        if ($('#deptSortable').data('ui-sortable')) {
            $('#deptSortable').sortable('disable');
        }
        $('.dept-pick').prop('disabled', true);
        $('.wizard-panel-btn').prop('disabled', true).addClass('disabled');
    }
});
</script>
@endsection
