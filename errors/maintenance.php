<?php
/**
 * FocusedTube - Maintenance Mode
 * 
 * Displayed when the site is in maintenance mode
 * 
 * @package FocusedTube
 * @version 1.0.0
 */

use FocusedTube\Security;

$message = $settings['maintenance']['message'] ?? 'We are currently performing maintenance. Please check back soon.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - <?php echo APP_NAME; ?></title>
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
        .maintenance-container {
            text-align: center;
            max-width: 500px;
        }
        .maintenance-icon {
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
        .status {
            display: inline-block;
            padding: 4px 12px;
            background: #f59e0b;
            color: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-icon">🔧</div>
        <div class="status">Maintenance Mode</div>
        <h1>We'll Be Back Soon</h1>
        <p><?php echo Security::escapeHtml($message); ?></p>
        <p style="font-size: 14px; color: #94a3b8;">
            Thank you for your patience.
        </p>
    </div>
</body>
</html>