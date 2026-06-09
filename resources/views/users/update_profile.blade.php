@extends('layouts.template_v1')
@section('content')
<style>
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
                <form class="custom-validations" id="updateprofileform" action="#" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="user_id" id="user_id"
                        value="@if(isset($data['user']['user_id'])){{$data['user']['user_id'] ?? ''}}@endif">
                    <div class="row">

                        <div class="col-md-6 mb-3">
                            <label>First Name</label>
                            <input type="text" class="form-control required  nameOnly " name="first_name"
                                id="first_name"
                                value="@if(isset($data['user']['first_name'])){{$data['user']['first_name'] ?? ''}}@endif"
                                placeholder="First Name" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Last Name</label>
                            <input type="text" class="form-control required  nameOnly " name="last_name" id="last_name"
                                value="@if(isset($data['user']['last_name'])){{$data['user']['last_name'] ?? ''}}@endif"
                                placeholder="Last Name" data-msg="Last Name" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Email id</label>
                            <input type="text" class="form-control required emailOnly nospaces" name="email_id"
                                value="@if(isset($data['user']['email_id'])){{$data['user']['email_id'] ?? ''}}@endif"
                                id="email_id" placeholder="Email Id" data-msg="Email id" readonly />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Phone</label>
                            <input type="text" name="mobile_no" class="form-control required numberOnly" id="mobile_no"
                                minlength="10" maxlength="10"
                                value="@if(isset($data['user']['mobile_no'])){{$data['user']['mobile_no'] ?? ''}}@endif"
                                placeholder="Phone" data-msg="Phone">
                        </div>

                    </div>
                    <div class="row">
                        <div class="col-md-12 text-center mt-3 mb-2">
                            <button type="button" id="updateProfile"
                                class="btn btn-primary waves-effect waves-light me-1">
                                Submit
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- end row -->
@push('scripts')
<script>
$(document).ready(function() {
    $('#updateProfile').unbind('click').click(function() {
        event.preventDefault();
        var form_id = $('#updateprofileform').attr('id');
        var action = "{{ url('/update_profile')}}";
        var json_data = {
            "formId": form_id,
            "url": action,
            "postKey": "insert"
        };
        if (validateFormdata(json_data)) {
            var form = $('#' + form_id)[0];
            var data = new FormData(form);
            ajaxRequestWithPromise(action, data, json_data.postKey, 1).then(function(response) {
                $(".preloader").hide();
                if (response.error == 0) {
                    // window.location.href =  "{{URL::to('/mapping-list')}}";  
                    $('#sales_org_id, #agency_id').val(null).trigger('change');
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