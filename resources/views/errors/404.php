<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; background: #f8f9fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .error-page { text-align: center; max-width: 500px; padding: 40px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .error-code { font-size: 72px; font-weight: bold; color: #dc3545; margin: 0; }
        .error-title { font-size: 24px; color: #495057; margin: 20px 0; }
        .error-message { color: #6c757d; margin-bottom: 30px; line-height: 1.5; }
        .back-button { display: inline-block; padding: 12px 24px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="error-page">
        <h1 class="error-code">404</h1>
        <h2 class="error-title">Page Not Found</h2>
        <p class="error-message"><?php echo $message ?? 'The page you are looking for could not be found.'; ?></p>
        <a href="/" class="back-button">Go Home</a>
    </div>
</body>
</html>