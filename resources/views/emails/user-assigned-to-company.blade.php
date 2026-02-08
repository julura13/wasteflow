<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigned to Company - WasteFlow</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            background-color: #f9fafb;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .header {
            background-color: rgb(34, 74, 64);
            padding: 30px 20px;
            text-align: center;
        }
        .header-logo {
            color: #ffffff;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .content {
            padding: 40px 30px;
        }
        .content-title {
            color: #2563eb;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .content-text {
            color: #374151;
            font-size: 16px;
            margin-bottom: 15px;
            line-height: 1.8;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #2563eb;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
        }
        .button:hover {
            background-color: #1d4ed8;
        }
        .footer {
            background-color: #f3f4f6;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }
        .info-box {
            background-color: #eff6ff;
            border-left: 4px solid #2563eb;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box-text {
            color: #1e40af;
            font-size: 14px;
        }
        .company-details {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 20px;
            margin: 20px 0;
            border-radius: 6px;
        }
        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 10px;
        }
        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
        }
        .role-manager {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .role-viewer {
            background-color: #f3f4f6;
            color: #374151;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="header-logo">WasteFlow</div>
        </div>
        
        <div class="content">
            <h1 class="content-title">Company Assignment</h1>
            
            <p class="content-text">
                Hello {{ $user->name }},
            </p>
            
            <p class="content-text">
                You have been assigned to a company in the WasteFlow Portal. You can now access orders and reports for this company.
            </p>
            
            <div class="company-details">
                <div class="company-name">{{ $company->name }}</div>
                <span class="role-badge role-{{ $role }}">
                    {{ ucfirst($role) }}
                </span>
            </div>
            
            <div class="info-box">
                <p class="info-box-text">
                    <strong>Your Access:</strong><br>
                    As a <strong>{{ ucfirst($role) }}</strong>, you can view orders and reports for {{ $company->name }}. 
                    @if($role === 'manager')
                        You also have additional management permissions.
                    @endif
                </p>
            </div>
            
            <div class="button-container">
                <a href="{{ $loginUrl }}" class="button">Access Portal</a>
            </div>
            
            <p class="content-text" style="font-size: 14px; color: #6b7280;">
                If you have any questions or need assistance, please contact your administrator.
            </p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} WasteFlow. All rights reserved.</p>
            <p style="margin-top: 5px;">This is an automated email, please do not reply.</p>
        </div>
    </div>
</body>
</html>

