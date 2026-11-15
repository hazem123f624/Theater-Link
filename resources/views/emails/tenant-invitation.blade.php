<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Workspace invitation</title></head>
<body>
    <p>You have been invited to join <strong>{{ $tenant->name }}</strong> on TheaterLink.</p>
    <p>Use this invitation token to complete your account:</p>
    <p><code>{{ $invitation->token }}</code></p>
    <p>This invitation expires on {{ $invitation->expires_at }}.</p>
</body>
</html>
