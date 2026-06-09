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
                                                    <img src="{{ getAssetUrl('images/logo-pdts.svg') }}" alt="{{ config('app.name') }}" height="90"
                                                        class="auth-logo logo-dark mx-auto">

                                                </a>
                                            </div>

                                            <h4 class="font-size-18 mt-4">Welcome Back !</h4>
                                            <p class="text-muted">Sign in to continue.</p>
                                        </div>

                                        <div class="p-2 mt-5">
                                            <form class="" action="{{route('forgotemail.verification')}}" method="POST">
                                                @csrf
                                                <div class="mb-3 auth-form-group-custom mb-4">
                                                    <i class="ri-user-2-line auti-custom-input-icon"></i>
                                                    <label for="username">Email Id</label>
                                                    <input type="text" class="form-control" name="email_id" id="email_id" placeholder="Enter Email Id">
                                                </div>
                                                <div class="mt-4 text-center">
                                                    <button class="btn btn-primary w-md waves-effect waves-light" type="submit">Submit</button>
                                                    <a href="{{url('/')}}" class="btn btn-primary w-md waves-effect waves-light">Back</a>
                                                </div>

                                                <div class="mt-4 text-center">
                                                    <!-- <a href="auth-recoverpw.html" class="text-muted"><i class="mdi mdi-lock me-1"></i> Forgot your password?</a> -->
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
    <script src="{{ URL:: to('assets/libs/toaster/toaster.js')}}"></script>
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
</body>

</html>