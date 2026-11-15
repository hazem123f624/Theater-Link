<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>Workspace invitation</title></head>
<body>
    <p>You have been invited to join <strong><?php echo e($tenant->name); ?></strong> on TheaterLink.</p>
    <p>Use this invitation token to complete your account:</p>
    <p><code><?php echo e($invitation->token); ?></code></p>
    <p>This invitation expires on <?php echo e($invitation->expires_at); ?>.</p>
</body>
</html>
<?php /**PATH /home/masarat/Desktop/TheaterLink/resources/views/emails/tenant-invitation.blade.php ENDPATH**/ ?>