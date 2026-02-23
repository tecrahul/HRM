@component('mail::message')
# SMTP Connectivity Test

This message confirms that your SMTP configuration for **{{ $appName }}** is working.

- Triggered by: {{ $requestedBy }}
- Sent at: {{ now()->format('M d, Y h:i A') }}

If you did not request this test, you can ignore the email.

Thanks,
{{ $appName }} Team
@endcomponent
