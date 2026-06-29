<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #f8fafc;
            padding: 20px;
        }
        .error-container {
            text-align: center;
            max-width: 500px;
        }
        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 32px;
            color: #0f172a;
            margin-bottom: 10px;
        }
        p {
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #ff3d00;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #e03500;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">🔍</div>
        <h1>404 - Page Not Found</h1>
        <p>The page you're looking for doesn't exist or has been moved.</p>
        <a href="/" class="btn">Return to Home</a>
    </div>
</body>
</html>