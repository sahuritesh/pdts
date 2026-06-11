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

  body{
    font-family: "Inter", sans-serif;

    margin:0;
    padding:0;

    position:relative;

    background-image:
        linear-gradient(
            rgba(5,10,25,0.65),
            rgba(5,10,25,0.7)
        ),
        url(assets/images/login-bg.jpg);

    background-size:cover;
    background-position:center;
    background-repeat:no-repeat;
    background-attachment:fixed;
}
body::before{
    content:"";

    position:fixed;
    inset:0;

    background:
        radial-gradient(circle at 20% 20%, rgba(59,130,246,.25), transparent 35%),
        radial-gradient(circle at 80% 80%, rgba(139,92,246,.20), transparent 35%);

    animation:bgMove 12s ease-in-out infinite alternate;

    z-index:0;
}

@keyframes bgMove{
    from{
        transform:scale(1);
    }
    to{
        transform:scale(1.2);
    }
}

.login-container{
    min-height:98vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:0px;
    gap:30px;
      position:relative;
    z-index:10;
}

/* LEFT SIDE */

.login-left{
    width:42%;
    max-width:500px;

    position:relative;
    /* overflow:hidden; */

    padding:25px;

    border-radius:30px;

    /* background:rgba(255,255,255,0.05);
    backdrop-filter:blur(12px);

    border:1px solid rgba(255,255,255,0.08); */

    display:flex;
    align-items:center;
    justify-content:center;
}

/* Floating Glow 1 */

.login-left::before{
    content:"";

    position:absolute;

    width:350px;
    height:350px;

    top:-100px;
    left:-120px;

    border-radius:50%;

    background:
    radial-gradient(
        rgba(59,130,246,.55),
        transparent 70%
    );

    animation:floatGlow1 8s ease-in-out infinite;
}

/* Floating Glow 2 */

.login-left::after{
    content:"";

    position:absolute;

    width:280px;
    height:280px;

    bottom:-80px;
    right:-80px;

    border-radius:50%;

    background:
    radial-gradient(
        rgba(139,92,246,.45),
        transparent 70%
    );

    animation:floatGlow2 10s ease-in-out infinite;
}

.login-left-content{
    position:relative;
    z-index:2;
}
.login-left-content::before{
    content:"";

    position:absolute;

    inset:-100px;

    background-image:
        radial-gradient(rgba(255,255,255,.18) 1px, transparent 1px);

    background-size:22px 22px;

    opacity:.4;

    z-index:-1;

    animation:moveDots 25s linear infinite;
}

.login-left-content img{
    max-width:250px;
    margin-bottom:20px;

    /* animation:logoFloat 4s ease-in-out infinite; */
}

@keyframes logoFloat{
    0%,100%{
        transform:translateY(0);
    }
    50%{
        transform:translateY(-10px);
    }
}

.login-left-content h1{
    font-size:34px;
    font-weight:700;
    color:#ffffff;
    margin-bottom:15px;
}

.login-left-content p{
    color:#fff;
    line-height:1.8;
    margin-bottom:25px;
}

.feature-list{
    display:flex;
    flex-direction:column;
    gap:14px;
    margin-top:25px;
}

.feature-list span{
    display:flex;
    align-items:center;
    gap:8px;

    color:#fff;
    font-size:14px;
    font-weight:500;

    padding:8px 16px;

    background:rgba(255,255,255,0.08);
    border:1px solid rgba(255,255,255,0.10);

    border-radius:12px;

    backdrop-filter:blur(10px);
}

.feature-list span i{
    width:36px;
    height:36px;

    display:flex;
    align-items:center;
    justify-content:center;

    border-radius:10px;

    background:rgba(255,255,255,0.15);

    font-size:18px;

    color:#4ade80;
}

/* RIGHT SIDE */
.form-left-20{
    padding-left:15px !important;
}
.login-right{
    width:100%;
    max-width:500px;

    background:#fff;
    padding:26px;

    border-radius:20px;

    box-shadow:
    0 10px 40px rgba(15,23,42,.08);
}

.loginbtnSection{
    margin-top:25px;
}

.btn-login{
    width:100%;
    height:54px;

    border:none;
    border-radius:12px;

    background:linear-gradient(
        135deg,
        #00a6a6,
        #1f8ef1
    );

    color:#fff;

    font-size:15px;
    font-weight:600;

    cursor:pointer;
    transition:.3s;

    box-shadow:
        0 10px 25px rgba(0,166,166,.25);
}

.btn-login:hover{
    background:linear-gradient(
        135deg,
        #009292,
        #1877d9
    );

    transform:translateY(-2px);

    box-shadow:
        0 15px 30px rgba(31,142,241,.30);
}
.captcha-wrapper{
    margin-top:8px;
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
}

.captcha-wrapper img{
    height:50px;
    border-radius:10px;
    border:1px solid #e2e8f0;
}

.captcha-refresh{
    color:#2563eb;
    text-decoration:none;
    font-weight:600;
}
.form-group-modern{
    margin-bottom:18px;
}

.form-group-modern label{
    display:block;
    margin-bottom:8px;
    font-size:14px;
    font-weight:600;
    color:#334155;
}

.input-wrapper{
    position:relative;
}

.input-icon{
    position:absolute;
    left:15px;
    top:50%;
    transform:translateY(-50%);
    color:#94a3b8;
}

.form-group-modern input{
    width:100%;
    height:48px;

    border:1px solid #dbe2ea;
    border-radius:12px;

    padding:0 15px 0 45px;

    background:#fff;
    font-size:14px;
}

.form-group-modern input:focus{
    outline:none;
    border-color:#2563eb;
}

.toggle-password{
    position:absolute;
    right:15px;
    top:50%;
    transform:translateY(-50%);
    cursor:pointer;
}
.login-header{
    text-align:center;
    margin-bottom:30px;
}

.login-title{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:10px;

    font-size:28px;
    font-weight:700;
    color:#0f172a;
}

.login-title i{
    color:#2563eb;
    font-size:24px;
}

.login-header p{
    margin-top:8px;
    color:#64748b;
    margin-bottom: 5px;
}
@keyframes floatGlow1{
    0%,100%{
        transform:translate(0,0) scale(1);
    }
    50%{
        transform:translate(40px,30px) scale(1.15);
    }
}

@keyframes floatGlow2{
    0%,100%{
        transform:translate(0,0) scale(1);
    }
    50%{
        transform:translate(-30px,-40px) scale(1.1);
    }
}

@keyframes moveDots{
    from{
        transform:translateY(0);
    }
    to{
        transform:translateY(-80px);
    }
}
@media(max-width:992px){
.animated-bg, .login-left::after, body::before, .login-left-content::before{
    display:none;
}
    .login-container{
        flex-direction:column;
        gap:20px;
        padding:20px;
    }

    .login-left{
        width:100%;
        max-width:100%;
        text-align:center;
    }

    .login-left-content{
        text-align:center;
    }

    .login-left-content h1{
        font-size:32px;
    }

    .login-right{
        max-width:100%;
    }
}

@media(max-width:576px){

    .login-container{
        padding:15px;
        width: 100%;
    }

    .login-right{
        padding:25px 20px;
    }

    .login-title{
        font-size:24px;
    }

    .login-left-content h1{
        font-size:26px;
    }

    .login-left-content img{
        /* max-width:170px; */
        margin-bottom:10px;
    }

    .captcha-wrapper{
        flex-direction:column;
        align-items:flex-start;
    }
}
/* Animated Background */

.animated-bg{
    position:fixed;
    inset:0;
    overflow:hidden;
    z-index:0;
    pointer-events:none;
}

.animated-bg span{
    position:absolute;
    display:block;
    border-radius:50%;

    background:rgba(59,130,246,.15);
    backdrop-filter:blur(10px);

    animation:floatBubble linear infinite;
}

/* Circle 1 */
.animated-bg span:nth-child(1){
    width:300px;
    height:300px;
    left:5%;
    bottom:-350px;
    animation-duration:20s;
}

/* Circle 2 */
.animated-bg span:nth-child(2){
    width:200px;
    height:200px;
    left:25%;
    bottom:-250px;
    animation-duration:15s;
    animation-delay:2s;
}

/* Circle 3 */
.animated-bg span:nth-child(3){
    width:350px;
    height:350px;
    right:10%;
    bottom:-400px;
    animation-duration:25s;
    background:rgba(139,92,246,.15);
}

/* Circle 4 */
.animated-bg span:nth-child(4){
    width:180px;
    height:180px;
    right:35%;
    bottom:-250px;
    animation-duration:18s;
    animation-delay:4s;
}

/* Circle 5 */
.animated-bg span:nth-child(5){
    width:120px;
    height:120px;
    left:50%;
    bottom:-180px;
    animation-duration:12s;
}

@keyframes floatBubble{
    0%{
        transform:translateY(0) rotate(0deg);
        opacity:0;
    }

    10%{
        opacity:1;
    }

    100%{
        transform:translateY(-120vh) rotate(360deg);
        opacity:0;
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

                <h1>Project Delay Tracking System</h1>

                <p>
                    Monitor projects, track delays, manage milestones,
                    and generate real-time reports from a single dashboard.
                </p>

               <div class="feature-list">
    <span>
        <i class="ri-time-line"></i>
        Real-time Tracking
    </span>

    <span>
        <i class="ri-line-chart-line"></i>
        Project Monitoring
    </span>

    <span>
        <i class="ri-file-chart-line"></i>
        Smart Reporting
    </span>
</div>
            </div>
        </div>

        <div class="login-right">
            <div class="login-header">
                @if(file_exists(public_path('assets/images/logo.png')))

                @endif
                <h2 class="login-title">
    <i class="fas fa-user-lock"></i> Sign In
</h2>
                <p>Enter your credentials to access your accounts</p>
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

<div class="animated-bg">
    <span></span>
    <span></span>
    <span></span>
    <span></span>
    <span></span>
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