<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Workspace invitation</title></head>
<body>
    <p>You have been invited to join <strong>{{ $tenant->name }}</strong> on TheaterLink.</p>
    <p>
        <a href="{{ rtrim((string) config('tenancy.frontend_url'), '/') }}/invitations/accept?token={{ $invitation->token }}">
            Accept the invitation
        </a>
    </p>
    <p>If the link does not work, use this invitation token:</p>
    <p><code>{{ $invitation->token }}</code></p>
    <p>This invitation expires on {{ $invitation->expires_at }}.</p>
</body>
</html>
