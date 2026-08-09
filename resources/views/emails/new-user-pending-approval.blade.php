<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New User Registration - WasteFlow</title>
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
        .user-details {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 20px;
            margin: 20px 0;
            border-radius: 6px;
        }
        .user-name {
            font-size: 20px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 6px;
        }
        .user-meta {
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="header-logo">WasteFlow</div>
        </div>

        <div class="content">
            <h1 class="content-title">New User Registration</h1>

            <p class="content-text">
                A new user has registered for the WasteFlow Portal and is awaiting your approval before they can sign in.
            </p>

            <div class="user-details">
                <div class="user-name">{{ $registeredUser->name }}</div>
                <div class="user-meta">{{ $registeredUser->email }}</div>
                @if($registeredUser->phone)
                    <div class="user-meta">{{ $registeredUser->phone }}</div>
                @endif
            </div>

            <div class="button-container">
                <a href="{{ $usersUrl }}" class="button">Review &amp; Approve</a>
            </div>

            <p class="content-text" style="font-size: 14px; color: #6b7280;">
                They will not be able to log in until an administrator activates their account.
            </p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} WasteFlow. All rights reserved.</p>
            <p style="margin-top: 5px;">This is an automated email, please do not reply.</p>
        </div>
    </div>
</body>
</html>
