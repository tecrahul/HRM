<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HRM Login Credentials</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Arial,sans-serif;color:#0f172a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;padding:24px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
                <tr>
                    <td style="padding:20px 24px;background:#ecfdf5;border-bottom:1px solid #d1fae5;">
                        <h1 style="margin:0;font-size:20px;line-height:1.3;color:#065f46;">Your account is ready</h1>
                        <p style="margin:8px 0 0 0;font-size:14px;color:#065f46;">Use these temporary credentials to sign in.</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:20px 24px;">
                        <p style="margin:0 0 12px 0;font-size:14px;">Hello {{ $employeeName }},</p>
                        <p style="margin:0 0 16px 0;font-size:14px;line-height:1.6;color:#334155;">
                            Your HRM access has been created by {{ $sentByName }}. Please log in and change your password immediately.
                        </p>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;">
                            <tr>
                                <td style="padding:10px 14px;font-size:13px;color:#475569;width:35%;">Login URL</td>
                                <td style="padding:10px 14px;font-size:13px;"><a href="{{ $loginUrl }}" style="color:#2563eb;">{{ $loginUrl }}</a></td>
                            </tr>
                            <tr>
                                <td style="padding:10px 14px;font-size:13px;color:#475569;border-top:1px solid #e2e8f0;">Email</td>
                                <td style="padding:10px 14px;font-size:13px;border-top:1px solid #e2e8f0;">{{ $employeeEmail }}</td>
                            </tr>
                            <tr>
                                <td style="padding:10px 14px;font-size:13px;color:#475569;border-top:1px solid #e2e8f0;">Temporary Password</td>
                                <td style="padding:10px 14px;font-size:13px;border-top:1px solid #e2e8f0;"><strong>{{ $temporaryPassword }}</strong></td>
                            </tr>
                        </table>

                        <p style="margin:16px 0 0 0;font-size:12px;color:#64748b;line-height:1.6;">
                            For security, this password is temporary and should be updated after first login.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
