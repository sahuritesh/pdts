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
    $spocUsers = $data['spoc_users'] ?? [];
    $selectedDeptIds = array_column($projectDepartments, 'department_id');
    $selectedZoneId = $project['zone_id'] ?? '';
    $selectedLocationId = $project['location_id'] ?? '';
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

    <h4 class="page-heading mb-0">
        <span class="page-heading-icon">
            <i class="ri-building-2-line"></i>
        </span>
        {{ $pageTitle }}
    </h4>

    <a href="{{ getProjectUrl('projects-list') }}" class="back-project-btn">
        <i class="ri-arrow-left-line"></i>
        Back to Projects
    </a>

</div>

                <input type="hidden" id="hdnForeignKeyId" value="{{ $projectId }}">
                <a id="commonActionButton" href="{{ getProjectUrl('projects-list') }}" style="display:none"></a>

                <div id="basic-example" role="application" class="wizard clearfix">
                    <div class="steps clearfix">
                        <ul role="tablist">
                            <li role="tab" class="first customWizard customWizard-0 @if($initialStep === 0) current @else disabled @endif"
                                @if($enableClick) onclick="enableDisableSections(0)" @endif>
                                <a class="customWizardSteps"><span class="number"></span> General</a>
                            </li>
                            <li role="tab" class="customWizard customWizard-1 @if($initialStep === 1) current @else disabled @endif"
                                @if($enableClick) onclick="enableDisableSections(1)" @endif>
                                <a class="customWizardSteps"><span class="number"></span> Departments</a>
                            </li>
                            <li role="tab" class="customWizard customWizard-2 @if($initialStep === 2) current @else disabled @endif"
                                @if($enableClick) onclick="enableDisableSections(2)" @endif>
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
                                                @include('project_wizard.partials.dept-workflow-fields', [
                                                    'pd' => $pd,
                                                    'status' => $status,
                                                    'showSpoc' => true,
                                                    'spocUsers' => $spocUsers,
                                                ])
                                                @endif
                                            </div>
                                            <div class="col-md-4">
                                                @include('project_wizard.partials.dept-panel-actions', [
                                                    'pd' => $pd,
                                                    'encPdId' => $encPdId,
                                                    'isPending' => $isPending,
                                                ])
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
</style>
@endpush

<script>
var projectDeptMaster = @json($masterDepartments);
var spocUserOptions = @json($spocUsers);
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

    bindDepartmentWorkflowHandlers({
        saveUrl: "{{ getProjectUrl('save_project_department') }}",
        statusUrl: "{{ getProjectUrl('update_department_status') }}",
        csrfToken: '{{ csrf_token() }}',
        reloadMode: 'page'
    });

    initSpocUserControls();
}

function renderSpocSelectOptions($select, selectedId) {
    if ($.fn.select2 && $select.hasClass('select2-hidden-accessible')) {
        $select.select2('destroy');
    }
    var html = '<option value="">Select SPOC user</option>';
    (spocUserOptions || []).forEach(function(user) {
        var sel = (String(selectedId) === String(user.id)) ? ' selected' : '';
        html += '<option value="' + user.id + '"' + sel + '>' + user.label + '</option>';
    });
    $select.html(html);
    if ($.fn.select2) {
        $select.select2({ width: '100%', placeholder: 'Select SPOC user' });
    }
}

function refreshAllSpocSelects(selectUserId, $activeBlock) {
    $('.spoc-user-select').each(function() {
        var $sel = $(this);
        var isActive = $activeBlock && $activeBlock.find('.spoc-user-select')[0] === this;
        var val = (isActive && selectUserId) ? selectUserId : $sel.val();
        renderSpocSelectOptions($sel, val);
        $sel.val(val).trigger('change');
    });
}

function initSpocUserControls() {
    if ($.fn.select2) {
        $('.spoc-user-select').each(function() {
            if (!$(this).hasClass('select2-hidden-accessible')) {
                $(this).select2({ width: '100%', placeholder: 'Select SPOC user' });
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

    $('.toggle-spoc-add-form').off('click').on('click', function() {
        var $block = $(this).closest('.spoc-user-block');
        $block.find('.spoc-add-form').slideToggle(150);
    });

    $('.cancel-spoc-add').off('click').on('click', function() {
        $(this).closest('.spoc-add-form').slideUp(150);
    });

    $('.save-spoc-user').off('click').on('click', function() {
        var $btn = $(this);
        var $block = $(this).closest('.spoc-user-block');
        var payload = new FormData();
        payload.append('_token', '{{ csrf_token() }}');
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
                    if (res.users && res.users.length) {
                        spocUserOptions = res.users;
                    } else if (res.user) {
                        spocUserOptions = spocUserOptions || [];
                        var exists = spocUserOptions.some(function(u) { return String(u.id) === String(res.user.id); });
                        if (!exists) {
                            spocUserOptions.push(res.user);
                        }
                    }
                    refreshAllSpocSelects(res.user ? res.user.id : '', $block);
                    $block.find('.spoc-add-form').slideUp(150);
                    $block.find('.spoc-add-first-name, .spoc-add-last-name, .spoc-add-email, .spoc-add-mobile, .spoc-add-password').val('');
                    parseFormErrors(res, 'success');
                } else {
                    parseFormErrors(res, 'error');
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

    var initZone = $('#wizard_zone_id').val();
    var initLoc = $('#wizard_location_id').data('selected') || '';
    if (initZone) {
        loadWizardLocations(initZone, initLoc);
    }

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
