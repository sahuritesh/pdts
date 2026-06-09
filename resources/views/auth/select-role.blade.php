<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1.0, shrink-to-fit=no">
	<link href="{{ getProjectUrl('assets/images/favicon.svg') }}" rel="icon" type="image/svg+xml" />
	<title>{{ env('APP_NAME', 'Project Delay Tracking System') }} - {{ $pageTitle ?? '' }}</title>
	<meta name="description" content="Select Role - {{ config('app.name') }}">

	<!-- Stylesheet
========================= -->
	<link href="{{URL::to('/assets/frontend/css/bootstrap.css')}}" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="{{URL::to('/vendor/font-awesome/css/all.min.css')}}" />
	<link href="{{URL::to('/assets/frontend/css/style.css')}}" rel="stylesheet">
	<link href="{{URL::to('/assets/frontend/css/responsive.css')}}" rel="stylesheet">
	<!-- Colors Css -->
	<link id="color-switcher" type="text/css" rel="stylesheet" href="#" />
    <link href="{{ URL:: to('assets/libs/toaster/toaster.css')}}" id="app-style" rel="stylesheet" type="text/css" />
	<script> 
        // $(document).ready(function() { 
            function disableBack() { 
                window.history.forward();
                // window.location = 'dashboard'; 
            } 
            window.onload = disableBack(); 
            window.onpageshow = function(e) { 
                if (e.persisted) 
                    disableBack(); 
            } 
        // }); 
    </script> 
</head>
<body>

	<div id="main-wrapper" class="login-box bg-dark">
		<div class="container-fluid">
			<div class="row g-0 min-vh-100">
				<!-- Welcome Text
      ========================= -->
				<div class="col-lg-7 col-md-6 shadow-lg">
					<div class="left-box d-flex align-items-center rounded-0 rounded-end-0 h-100">
						<div class="hero-bg hero-bg-scroll" style="background-image:url('assets/images/login-bg.svg');">
						</div>
					</div>
				</div>
				<!-- Welcome Text End -->

				<!-- Role Selection Form
      ========================= -->
				<div data-bs-theme="dark" class="col-lg-5 col-md-6 shadow-lg d-flex rounded-0 rounded-start-0 bg-white">
					<div class="container">
						<div class="rightLoginWrapp">
							
							<img src="{{ getProjectUrl('assets/images/logo-pdts.svg') }}" width="190" alt="{{ config('app.name') }}" />

							<div class="loginWelWrapp">
								<div class="welConBlock">
									@php
										$selectedRole = session('selected_role');
									@endphp
									@if($selectedRole)
										<h4 class="loginHeading">Switch Your Role <br>
										<small>Change how you want to access</small></h4>
									@else
										<h4 class="loginHeading">Select Your Role <br>
										<small>Choose how you want to access</small></h4>
									@endif
								</div>

								@if($selectedRole)
									@php
										$currentRedirect = session('selected_role_redirect', 'dashboard');
										$backUrl = url($currentRedirect);
									@endphp
									<a href="{{$backUrl}}" class="backToHomeLink"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
								@else
									<a href="{{url('/')}}" class="backToHomeLink"><i class="fa fa-arrow-left"></i> Back to Home</a>
								@endif
							</div>
							
							<form class="form-dark loginform" method="POST" action="{{url('switch-role')}}" id="roleSelectionForm">
								@csrf
								<div class="row g-3 mb-4">
									@foreach($availableRoles as $index => $role)
									<div class="col-md-6">
										<div class="role-card-option" data-role-id="{{$role['role_id']}}" data-role-type="{{$role['type']}}">
											<div class="role-card-inner">
												<div class="role-icon-wrapper">
													@if($role['type'] == 'frontend')
														<img src="{{URL::to('/assets/frontend/images/icons/member-icon.svg')}}" alt="Member Icon" class="role-icon">
													@else
														<img src="{{URL::to('/assets/frontend/images/icons/conference-icon.svg')}}" alt="Admin Icon" class="role-icon">
													@endif
												</div>
												<h5 class="role-title">{{$role['role_name']}}</h5>
												<p class="role-description">{{$role['role_description'] ?: $role['description']}}</p>
												@if($role['count'] > 0)
												<span class="role-badge">{{$role['count']}} Registration(s)</span>
												@endif
												<div class="role-radio-wrapper">
													<input type="radio" name="role_id" value="{{$role['role_id']}}" 
														   id="role_{{$role['role_id']}}" 
														   class="role-radio" 
														   data-redirect="{{$role['redirect']}}"
														   required>
													<label for="role_{{$role['role_id']}}" class="role-label">Select</label>
												</div>
											</div>
										</div>
									</div>
									@endforeach
								</div>
								
								<div class="d-grid my-2">
									<button class="btn btn-primary signin-btn" type="submit" id='continue-button'>Continue</button>
								</div>
							</form>
							
						</div>
					</div>
				</div>
				<!-- Role Selection Form End -->
			</div>
		</div>
	</div>

	<script src="{{ URL:: to('assets/libs/jquery/jquery.min.js')}}"></script>
    <script src="{{ URL:: to('assets/libs/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
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

    $(document).ready(function() {
        // Handle role card click
        $('.role-card-option').on('click', function(e) {
            if ($(e.target).is('input[type="radio"]') || $(e.target).is('label')) {
                return;
            }
            
            const roleId = $(this).data('role-id');
            const radio = $('#role_' + roleId);
            
            if (radio.length) {
                radio.prop('checked', true);
                $('.role-card-option').removeClass('selected');
                $(this).addClass('selected');
            }
        });

        // Handle radio button change
        $('input[name="role_id"]').on('change', function() {
            $('.role-card-option').removeClass('selected');
            const roleId = $(this).val();
            $('[data-role-id="' + roleId + '"]').addClass('selected');
        });

        // Form validation
        // Note: Controller will determine redirect path based on selected role_id
        $('#roleSelectionForm').on('submit', function(e) {
            const selectedRole = $('input[name="role_id"]:checked');
            if (!selectedRole.length) {
                e.preventDefault();
                toastr.error("Please select a role to continue.");
                return false;
            }
        });
    });
    </script>

	<style>
		.role-card-option {
			cursor: pointer;
			margin-bottom: 15px;
		}

		.role-card-inner {
			border: 2px solid #e0e0e0;
			border-radius: 10px;
			padding: 25px 20px;
			text-align: center;
			transition: all 0.3s ease;
			background: #fff;
			height: 100%;
			display: flex;
			flex-direction: column;
			align-items: center;
		}

		.role-card-option:hover .role-card-inner {
			border-color: var(--color-blue);
			box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
			transform: translateY(-3px);
		}

		.role-card-option.selected .role-card-inner {
			border-color: var(--color-blue);
			background-color: #f0f8ff;
			box-shadow: 0 4px 12px rgba(0, 123, 255, 0.25);
		}

		.role-icon-wrapper {
			margin-bottom: 15px;
		}

		.role-icon {
			width: 60px;
			height: 60px;
			object-fit: contain;
		}

		.role-title {
		    font-size: 16px;
			font-weight: 600;
			color: #333;
			margin-bottom: 10px;
		}

		.role-description {
			font-size: 14px;
			color: #666;
			margin-bottom: 10px;
			flex-grow: 1;
		}

		.role-badge {
			display: inline-block;
			padding: 5px 12px;
			background-color:#FFC14A;
			color:#000;
			border-radius: 20px;
			font-size: 12px;
			font-weight: 500;
			margin-bottom: 15px;
		}

		.role-radio-wrapper {
			margin-top: auto;
		}

		.role-radio {
			width: 18px;
			height: 18px;
			cursor: pointer;
			margin-right: 8px;
		}

		.role-label {
			font-size: 14px;
			color: #333;
			cursor: pointer;
			margin: 0;
		}

		.loginHeading {
			color: #333;
		}

		.loginHeading small {
			color: #666;
			font-size: 14px;
		}
	</style>
</body>
</html>

