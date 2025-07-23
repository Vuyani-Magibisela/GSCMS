<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $subject ?? 'GDE SciBOTICS' ?></title>
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 0;
            background-color: #f8f9fa;
        }
        
        .email-container {
            background-color: #ffffff;
            margin: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .email-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .email-body {
            padding: 30px;
        }
        
        .email-body h2 {
            color: #333;
            font-size: 20px;
            margin-bottom: 20px;
        }
        
        .email-body p {
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            text-align: center;
            margin: 20px 0;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .info-box {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 5px 5px 0;
        }
        
        .warning-box {
            background-color: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 5px 5px 0;
        }
        
        .email-footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-top: 1px solid #e9ecef;
        }
        
        .email-footer p {
            margin: 5px 0;
        }
        
        .email-footer a {
            color: #667eea;
            text-decoration: none;
        }
        
        .social-links {
            margin: 15px 0;
        }
        
        .social-links a {
            display: inline-block;
            margin: 0 10px;
            color: #6c757d;
            text-decoration: none;
        }
        
        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 10px;
            }
            
            .email-header, .email-body {
                padding: 20px;
            }
            
            .btn {
                display: block;
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>🔬 GDE SciBOTICS</h1>
            <p>Competition Management System</p>
        </div>
        
        <div class="email-body">
            <?= $content ?? '' ?>
        </div>
        
        <div class="email-footer">
            <p><strong>GDE SciBOTICS Competition Management System</strong></p>
            <p>This email was sent automatically. Please do not reply to this email.</p>
            
            <div class="social-links">
                <a href="#" title="Website">🌐 Website</a>
                <a href="#" title="Support">📧 Support</a>
                <a href="#" title="Facebook">📘 Facebook</a>
            </div>
            
            <p>
                If you have any questions, please contact us at 
                <a href="mailto:support@gscms.com">support@gscms.com</a>
            </p>
            
            <p style="margin-top: 20px; font-size: 11px;">
                © <?= date('Y') ?> GDE SciBOTICS. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>