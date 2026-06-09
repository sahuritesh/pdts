<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>{{ env('APP_NAME', 'Project Delay Tracking System') }} - {{ $pageTitle ?? 'Login' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="" name="description" />
    <meta content="Themesdesign" name="author" />
    <!-- App favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ getProjectUrl('assets/images/favicon.svg') }}">
    <link rel="shortcut icon" href="{{ getProjectUrl('assets/images/favicon.svg') }}">

    <!-- Bootstrap Css -->
    <link href="{{ getProjectUrl('assets/css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet"
        type="text/css" />
    <!-- Icons Css -->
    <link href="{{ getProjectUrl('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="{{ getProjectUrl('assets/css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />
    <link href="{{ getProjectUrl('assets/libs/toaster/toaster.css') }}" id="app-style" rel="stylesheet"
        type="text/css" />

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
    {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', sans-serif;
        /* background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); */
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background-image: url('assets/images/login-bg.svg');
        background-size: cover;
    }

   /* ================= MAIN CONTAINER ================= */

.login-container{
    width:100%;
    max-width:1100px;

    display:flex;
    align-items:stretch;

    border-radius:28px;

    overflow:hidden;

    position:relative;

    /* box-shadow:
    0 20px 60px rgba(0,0,0,0.18); */

    min-height:auto;
}

/* ================= LEFT ================= */

.login-left{
    flex:1;

    display:flex;
    align-items:center;
    justify-content:center;

    padding:60px 50px;

    position:relative;

    overflow:hidden;

    background: linear-gradient(135deg, rgba(15, 118, 110, 0.92), rgba(30, 64, 175, 0.88));
}

.login-left-content{
    position:relative;
    z-index:2;

    max-width:420px;
}

.login-left-content img{
    max-width:220px;
    width:100%;
    height:auto;
}

.login-left-content h1{
    font-size:38px;
    font-weight: 700;
    margin-bottom: 5px;
    margin-top: 8px;
    color: #ffffff;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
}

.login-left-content p{
    margin-top:10px;
    line-height:1.8;
    color: rgba(255, 255, 255, 0.92);
}

/* ================= RIGHT ================= */

.login-right{
    flex:1;

    padding:35px;

    background:#fff;

    display:flex;
    flex-direction:column;
    justify-content:center;

    position:relative;

    border-radius:24px;
}

/* ================= HEADER ================= */

.login-header{
    margin-bottom:12px;
}

.login-title{
    display:flex;
    align-items:center;
    gap:14px;

    font-size:32px;
    font-weight:800;

    color:#0f172a;
}

.login-title i{
    width:52px;
    height:52px;

    border-radius:16px;

    display:flex;
    align-items:center;
    justify-content:center;

    background:
    linear-gradient(135deg,#2563eb,#7c3aed);

    color:#fff;

    font-size:20px;
}

.login-header p{
    margin-top:12px;

    color:#64748b;

    line-height:1.7;

    font-size:14px;
}

/* ================= FORM ================= */

.form-group-modern{
    margin-bottom:12px;
}

.form-group-modern label{
    display:block;

    margin-bottom:8px;

    font-size:14px;
    font-weight:600;

    color:#0f172a;
}

/* Input Wrapper */

.input-wrapper{
    position:relative;
}

/* Icons */

.input-icon{
    position:absolute;

    left:18px;
    top:50%;

    transform:translateY(-50%);

    font-size:18px;

    color:#94a3b8;
}

/* Inputs */

.form-group-modern input{
    width:100%;

    height:45px;

    padding:0 18px 0 52px;

    border-radius:16px;

    border:1px solid #e2e8f0;

    background:#f8fafc;

    font-size:14px;

    transition:.3s ease;
}

.form-group-modern input:focus{
    outline:none;

    border-color:#2563eb;

    background:#fff;

    box-shadow:
    0 0 0 4px rgba(37,99,235,0.10);
}

/* Password Toggle */

.toggle-password{
    position:absolute;

    right:18px;
    top:50%;

    transform:translateY(-50%);

    font-size:18px;

    color:#94a3b8;

    cursor:pointer;
}

/* ================= CAPTCHA ================= */

.captcha-wrapper{
    display:flex;
    align-items:center;
    flex-wrap:wrap;

    gap:12px;

    margin-top:12px;
}

.captcha-wrapper img{
    height:48px;

    max-width:100%;

    border-radius:12px;

    border:1px solid #e2e8f0;

    background:#fff;

    padding:5px 10px;
}

.captcha-refresh{
    display:flex;
    align-items:center;
    gap:6px;

    color:#2563eb;

    font-size:14px;
    font-weight:600;

    text-decoration:none;
}

/* ================= BUTTON ================= */

.loginbtnSection{
    margin-top:24px;
}

.btn-login{
    width:100%;

    height:54px;

    border:none;

    border-radius:16px;

    background:
    linear-gradient(135deg,#2563eb,#7c3aed);

    color:#fff;

    font-size:15px;
    font-weight:700;

    cursor:pointer;

    transition:.3s;

    box-shadow:
    0 15px 35px rgba(37,99,235,0.22);
}

.btn-login:hover{
    transform:translateY(-2px);
}

/* ================= RESPONSIVE ================= */

/* Large Tablets */

@media(max-width:992px){

    .login-container{
        flex-direction:column;
        max-width:700px;
    }

    .login-left{
        padding:45px 35px;
    }

    .login-left-content{
        text-align:center;
    }

    .login-left-content h1{
        font-size:34px;
    }

    .login-right{
        padding:40px 32px;
        /* border-radius:0; */
    }
}

/* Mobile */

@media(max-width:576px){

    body{
        padding:14px;
    }

    .login-container{
        border-radius:22px;
    }

    .login-left{
        padding:35px 22px;
    }

    .login-left-content img{
        max-width:170px;
    }

    .login-left-content h1{
        font-size:28px;
    }

    .login-left-content p{
        font-size:14px;
    }

    .login-right{
        padding:28px 20px;
    }

    .login-title{
        font-size:24px;
        gap:10px;
    }

    .login-title i{
        width:44px;
        height:44px;

        font-size:16px;
    }

    .login-header p{
        font-size:13px;
    }

    .form-group-modern{
        margin-bottom:15px;
    }

    .form-group-modern input{
        height:48px;

        font-size:13px;

        border-radius:14px;
    }

    .btn-login{
        height:50px;

        font-size:14px;
    }

    .captcha-wrapper{
        flex-direction:column;
        align-items:flex-start;
    }

    .captcha-wrapper img{
        width:auto;
    }
}
    </style>
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
    <div class="login-container">
        <div class="login-left">
            <div class="login-left-content">
                <img src="{{ getProjectUrl('assets/images/logo-pdts-light.svg') }}" alt="{{ config('app.name') }}">
                <h1>Welcome Back!</h1>
                <p>Sign in to the Project Delay Tracking System admin panel.</p>
            </div>
        </div>

        <div class="login-right">
            <div class="login-header">
                @if(file_exists(public_path('assets/images/logo.png')))

                @endif
                <h2 class="login-title">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </h2>
                <p>Enter your credentials to access your account</p>
            </div>

            <form action="{{ getProjectUrl('adminlogin-verification') }}" id="login-form" method="POST">
                @csrf

                <div class="form-group-modern">
                    <label for="email_id">Email Address</label>
                    <div class="input-wrapper">
                        <i class="ri-mail-line input-icon"></i>
                        <input type="text" maxlength="50" class="nospaces email" name="username" id="email_id"
                            placeholder="Enter your email address" required>
                    </div>
                    <span class="error error-keyup-7" style="display: none;"></span>
                </div>

                <!-- <div class="form-group-modern">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="ri-lock-password-line input-icon"></i>
                        <input type="password" class="form-control" name="password" id="password" placeholder="Enter your password" required>
                    </div>
                </div> -->

                <div class="form-group-modern">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="ri-lock-password-line input-icon"></i>

                        <input type="password" class="form-control" name="password" id="password"
                            placeholder="Enter your password" required>

                        <!-- Eye Icon -->
                        <i class="ri-eye-line toggle-password" id="togglePassword"></i>
                    </div>
                </div>


                <div class="form-group-modern">
                    <label for="captcha">Security Code</label>
                    <div class="input-wrapper">
                        <i class="ri-shield-check-line input-icon"></i>
                        <input type="text" class="form-control form-left-20" maxlength="6" name="captcha" id="captcha"
                            placeholder="Enter captcha" required>
                    </div>
                    <div class="captcha-wrapper">
                        <img id="captcha-img" src="{{ getProjectUrl('captcha') }}/{{$data['captcha']}}" alt="Captcha">
                        <a href="javascript:void(0)" onclick="refreshCaptcha()" class="captcha-refresh"
                            title="Refresh Captcha">
                            <i class="ri-refresh-line"></i>
                            Refresh
                        </a>
                    </div>
                </div>
                <div class="loginbtnSection">
                    <button type="button" id="lobin-button" class="btn-login">
                        <span>Sign In</span>
                    </button>
                </div>

            </form>
        </div>
    </div>



    <!-- JAVASCRIPT -->
    <script src="{{ getProjectUrl('assets/libs/jquery/jquery.min.js') }}"></script>
    <script src="{{ getProjectUrl('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ getProjectUrl('assets/libs/metismenu/metisMenu.min.js') }}"></script>
    <script src="{{ getProjectUrl('assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ getProjectUrl('assets/libs/node-waves/waves.min.js') }}"></script>
    <script src="{{ getProjectUrl('assets/js/app.js') }}"></script>
    <script src="{{ getProjectUrl('assets/libs/toaster/toaster.js') }}"></script>

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
        $("#lobin-button").click(function() {
            loginForm();
        });
    });

    function loginForm() {
        let email_id = $("#email_id").val();
        let password = $("#password").val();
        let captcha = $("#captcha").val();
        if (email_id && password && captcha) {
            $("#login-form").submit();
        } else {
            toastr.error("Email Id / Password / Captcha is required");
        }
    }
    $(document).on("keypress", "form", function(event) {
        if (event.keyCode === 13) {
            event.preventDefault();
            // $(this).submit();
            loginForm();
        }
    });
    $(".email").blur(function(e) {
        var inputVal = $(this).val();
        $("span.error-keyup-7").remove();
        var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
        if (!emailReg.test(inputVal)) {
            $(this).focus();
            $(this).after(
                '<span class="error error-keyup-7">Invalid Email Format.</span>'
            );
        }
    });
    $(".nospaces").on({
        keydown: function(e) {
            if (e.which === 32) return false;
        },
        change: function() {
            this.value = this.value.replace(/\s/g, "");
        },
    });

    function refreshCaptcha() {
        var captcha = "{{ getProjectUrl('captcha') }}/"
        var url = "{{ getProjectUrl('refreshCaptcha') }}";
        var data = {};

        ajaxRequestWithPromise(url, data, 'refresh_captcha', 0).then(function(res) {
            var resp = typeof res === 'string' ? JSON.parse(res) : res;
            if (resp.error == 0 || resp.error == "0") {
                $("#captcha-img").attr('src', captcha + resp.file);
            }
        }).catch(function(err) {
            console.error(err);
        });
    }

    for (let key in localStorage) {
        if (key.startsWith('ucFilterForm_')) {
            localStorage.removeItem(key);
        }
    }
    </script>


    <script>
    const togglePassword = document.getElementById("togglePassword");
    const password = document.getElementById("password");
    togglePassword.addEventListener("click", function() {
        const type = password.getAttribute("type") === "password" ? "text" : "password";
        password.setAttribute("type", type);
        // Icon change
        this.classList.toggle("ri-eye-line");
        this.classList.toggle("ri-eye-off-line");
    });
    </script>
</body>

</html>