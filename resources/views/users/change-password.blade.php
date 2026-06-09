@extends('layouts.template_v1')
<link rel="stylesheet" href="{{URL::to('/assets/css/select2.min.css')}}">
@section('content')
<style>
/* Tooltip style */
.tooltip {
    position: relative;
    cursor: pointer;
}

.tooltip-popup {
    display: none;
    position: absolute;
    background-color: #333;
    color: #fff;
    padding: 10px;
    border-radius: 5px;
    z-index: 1;
    bottom: -125%;
    left: 50%;
    transform: translateX(-50%);
}

.tooltip-popup ul {
    /* list-style-type: none; 
     padding: 0; */
    margin: 0;
}

.tooltip-popup li {
    margin-bottom: 5px;
}
/* ===== HEADING WITH ICON ===== */
.heading-icon {
  display: flex;
  align-items: center;
  gap: 10px;
}

/* ===== ICON BOX ===== */
.heading-icon i {
  width: 34px;
  height: 34px;
  border-radius: 8px;

  background: rgba(37, 99, 235, 0.1); /* soft corporate bg */
  color: #2563eb;

  display: flex;
  align-items: center;
  justify-content: center;

  font-size: 14px;
}
</style>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-title addFormBtn p-3">
                <h4 class="text-primary mb-0 heading-icon">
    <i class="fas fa-file-alt"></i>
    {{$pageTitle}}
</h4>
            </div>
            <div class="card-body pt-0">
                <form class="custom-validations" id="changepasswrdform" action="#" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="user_id" id="user_id" value="@if(isset($data['user'][0]['user_id'])){{$data['user'][0]['user_id'] ?? ''}}@endif">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Current Password</label>
                            <input type="password" class="form-control nospaces required" name="current_password" value="" id="current_password" placeholder="Current Password" data-msg="Current Password" />
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>New Password <span id="password-tooltip" ><i class="ri-information-fill"></i></span>
                            </label>
                            <div class="tooltip-popup" id="password-rules" >
                                <!-- Password rules content here -->
                                <ul>
                                    <li>Password requires a minimum length of 8 characters</li>
                                    <li>Password should contain a mixture of lowercase, uppercase, numbers and special characters</li>
                                    <li>Password Cannot be the same as the most recent previous 5 passwords</li>
                                </ul>
                            </div>
                            <input type="password" class="form-control required password nospaces" name="password" value="" id="password" placeholder="New Password" data-msg="New Password" />
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Confirm New Password <span id="cpassword-tooltip" ><i class="ri-information-fill"></i></span></label>
                            
                            <div class="tooltip-popup" id="cpassword-rules" >
                                <!-- Password rules content here -->
                                <ul>
                                    <li>Password requires a minimum length of 8 characters</li>
                                    <li>Password should contain a mixture of lowercase, uppercase, numbers and special characters</li>
                                    <li>Password Cannot be the same as the most recent previous 5 passwords</li>
                                </ul>
                            </div>
                            <input type="password" class="form-control required password nospaces" name="confirm_password" value="" id="confirm_password" placeholder="Confirm Password" data-msg="Confirm New Password" />
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mt-3 text-center mb-2">
                            <button type="button" id="changepassword" class="btn btn-primary waves-effect waves-light me-1">
                                Submit
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div> <!-- end row -->
@push('scripts')
<script src="{{URL::to('assets/js/select2.min.js')}}"></script>
<script>
const tooltip = document.getElementById('password-tooltip');
const tooltipPopup = document.getElementById('password-rules');

tooltip.addEventListener('mouseenter', () => {
    tooltipPopup.style.display = 'block';
});

tooltip.addEventListener('mouseleave', () => {
    tooltipPopup.style.display = 'none';
});

const ctooltip = document.getElementById('cpassword-tooltip');
const ctooltipPopup = document.getElementById('cpassword-rules');

ctooltip.addEventListener('mouseenter', () => {
    ctooltipPopup.style.display = 'block';
});

ctooltip.addEventListener('mouseleave', () => {
    ctooltipPopup.style.display = 'none';
});
    $(document).ready(function() {
        $('#changepassword').unbind('click').click(function() {
            event.preventDefault();
            var form_id = $('#changepasswrdform').attr('id');
            var action = "{{ url('/update_password')}}";
            var json_data = {
                "formId": form_id,
                "url": action,
                "postKey" : "insert"
            };
            if (validateFormdata(json_data)) {
            var form = $('#' + form_id)[0];
            var data = new FormData(form);
            ajaxRequestWithPromise(action, data, json_data.postKey, 1).then(function(response) {                
                $(".preloader").hide(); 
                if (response.error == 0) {
                                        
                } else {
                    return false;
                }
            }).catch(function(err) {
                $(".preloader").hide(); 
                console.log(err);
            })
        } else {
            $(".preloader").hide(); 
            return false;
        }
        });
    });
</script>
@endpush
@stack('scripts')
@endsection