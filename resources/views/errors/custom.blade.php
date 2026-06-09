<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Something Went Wrong</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .error-container {
            text-align: center;
            padding: 30px;
            background: #ffffff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        .error-container h1 {
            font-size: 5rem;
            color: #dc3545;
            margin-bottom: 10px;
        }
        .error-container h2 {
            font-size: 1.5rem;
            color: #6c757d;
            margin-bottom: 20px;
        }
        .error-container p {
            color: #6c757d;
        }
        .btn-custom {
            background-color: #dc3545;
            color: #ffffff;
            border: none;
        }
        .btn-custom:hover {
            background-color: #bb2d3b;
        }
        .error-icon {
            font-size: 6rem;
            color: #dc3545;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⚠️</div>
        <h1>Oops!</h1>
        <h2>Something Went Wrong</h2>
        <p>We couldn't process your request. Please try again later or contact support if the problem persists.</p>
        <a href="{{url('/')}}" class="btn btn-custom mt-3">Go Back Home</a>
    </div>

    <!-- Bootstrap JS (Optional for interactive features) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
