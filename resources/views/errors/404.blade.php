<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
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
            font-family: 'Arial', sans-serif;
        }
        .error-container {
            text-align: center;
            padding: 40px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
        }
        .error-title {
            font-size: 8rem;
            font-weight: bold;
            color: #dc3545;
            margin: 0;
        }
        .error-message {
            font-size: 1.5rem;
            color: #6c757d;
            margin: 10px 0 20px;
        }
        .btn-custom {
            color: #ffffff;
            background-color: #007bff;
            border: none;
            padding: 10px 20px;
            font-size: 1rem;
            text-transform: uppercase;
            border-radius: 5px;
        }
        .btn-custom:hover {
            background-color: #0056b3;
        }
        .illustration {
            margin: 20px 0;
        }
        .illustration img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-title">404</div>
        <p class="error-message">Sorry, the page you're looking for can't be found.</p>
        <div class="illustration">
            <img src="{{url('assets/images/404_notfound.jpg')}}" alt="404 Illustration">
        </div>
        <a href="{{url('/')}}" class="btn btn-custom">Go Back Home</a>
    </div>

    <!-- Bootstrap JS (Optional for interactive features) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
