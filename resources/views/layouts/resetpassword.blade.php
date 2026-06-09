<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>{{ env('APP_NAME', 'Project Delay Tracking System') }} - {{$pageTitle??""}}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Premium Multipurpose Admin & Dashboard Template" name="description" />
    <meta content="Themesdesign" name="author" />
    <!-- App favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ getAssetUrl('images/favicon.svg') }}">
    <link rel="shortcut icon" href="{{ getAssetUrl('images/favicon.svg') }}">

    <!-- Bootstrap Css -->
    <link href="{{ URL:: to('assets/css/bootstrap.min.css')}}" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="{{ URL:: to('assets/css/icons.min.css')}}" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="{{ URL:: to('assets/css/app.min.css')}}" id="app-style" rel="stylesheet" type="text/css" />
    <link href="{{ URL:: to('assets/libs/toaster/toaster.css')}}" id="app-style" rel="stylesheet" type="text/css" />
    <link href="{{ URL:: to('http://ksylvest.github.io/jquery-growl/stylesheets/jquery.growl.css')}}" id="app-datepicker" rel="stylesheet" type="text/css" />

</head>

<body class="auth-body-bg">
    <div>
        <div class="container-fluid p-0">
            <div class="row g-0">

                <div class="col-lg-6">
                    <div class="authentication-bg">

                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="authentication-page-content p-4 d-flex align-items-center min-vh-100">
                        <div class="w-100">
                            <div class="row justify-content-center">
                                <div class="col-lg-9">
                                    <div>
                                        <div class="text-center">
                                            <div>
                                                <a href="#" class="">
                                                    <img src="{{ getAssetUrl('images/logo-pdts.svg') }}" alt="{{ config('app.name') }}" height="90" class="auth-logo logo-dark mx-auto">

                                                </a>
                                            </div>

                                            <h4 class="font-size-18 mt-2">Welcome Back !</h4>
                                            <p class="text-muted">Reset Password</p>
                                        </div>

                                        <div class="p-2 mt-3">
                                            <form class="" action="#" method="POST" id="resetpasswordform">
                                                @csrf
                                                <div class="mb-3 auth-form-group-custom ">
                                                    <i class="ri-user-2-line auti-custom-input-icon"></i>
                                                    <label for="username">Email Id</label>
                                                    <input type="text" class="form-control" name="email_id" id="email_id" placeholder="Enter Email Id">
                                                </div>
                                                <div class="mb-3 auth-form-group-custom ">
                                                    <i class="ri-asterisk auti-custom-input-icon"></i>
                                                    <label for="">OTP</label>
                                                    <input type="text" class="form-control" name="otp" id="otp" placeholder="Enter One Time Access Code">
                                                </div>
                                                <div class="mb-3 auth-form-group-custom ">
                                                    <i class="ri-lock-2-line auti-custom-input-icon"></i>
                                                    <label for="">New Password</label>
                                                    <input type="text" class="form-control" name="new_password" id="new_password" placeholder="Enter New Password">
                                                </div>
                                                <div class="mb-3 auth-form-group-custom ">
                                                    <i class="ri-lock-2-line auti-custom-input-icon"></i>
                                                    <label for="">Confirm Password</label>
                                                    <input type="text" class="form-control" name="confirm_password" id="confirm_password" placeholder="Enter Confirm Password">
                                                </div>
                                                <div class="mt-2 text-center">
                                                    <button class="btn btn-primary w-md waves-effect waves-light" type="button" id="resetpwd">Submit</button>
                                                    <a href="{{url('/')}}" class="btn btn-primary w-md waves-effect waves-light">Back</a>
                                                </div>

                                                
                                            </form>
                                        </div>

                                        <div class="mt-5 text-center">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>



    <!-- JAVASCRIPT -->
    <script src="{{ URL:: to('assets/libs/jquery/jquery.min.js')}}"></script>
    <script src="{{ URL:: to('assets/libs/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
    <script src="{{ URL:: to('assets/libs/metismenu/metisMenu.min.js')}}"></script>
    <script src="{{ URL:: to('assets/libs/simplebar/simplebar.min.js')}}"></script>
    <script src="{{ URL:: to('assets/libs/node-waves/waves.min.js')}}"></script>
    <script src="{{ URL:: to('assets/js/app.js')}}"></script>
    <script src="{{ URL:: to('assets/js/ajaxPromise.js')}}"></script>
    <script src="{{ URL:: to('assets/js/common.js')}}"></script>
    <script src="{{ URL:: to('assets/libs/toaster/toaster.js')}}"></script>
    <script src="{{URL::to('http://ksylvest.github.io/jquery-growl/javascripts/jquery.growl.js')}}"></script>
    <script src="{{ getAssetUrl('libs/tinymce/tinymce.min.js') }}"></script>
    <script>
        @if(Session::has('success'))
        toastr.options = {
            "closeButton": true,
            "progressBar": true
        }
        toastr.success("{{ session('success') }}");
        @endif

        @if(Session::has('error'))
        toastr.options = {
            "closeButton": true,
            "progressBar": true
        }
        toastr.error("{{ session('error') }}");
        @endif
    </script>
    <script>
    $(document).ready(function() {
        $('#resetpwd').unbind('click').click(function() {
            event.preventDefault();
            var form_id = $('#resetpasswordform').attr('id');
            var action = "{{ url('/resetpassword_verification')}}";
            var json = {
                "formId": form_id,
                "url": action,
                "postKey": "insert"
            };
            if (validateFormdata(json)) {
            var form = $('#' + form_id)[0];
            var data = new FormData(form);
            ajaxRequestWithPromise(action, data, json.postKey, 1).then(function(response) {                
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
</body>

</html>