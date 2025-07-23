<?php
$content = ob_get_clean();
ob_start();
?>

<h2>Welcome to GDE SciBOTICS!</h2>

<p>Hello <?= htmlspecialchars($user->getDisplayName()) ?>,</p>

<p>Congratulations! Your account has been successfully created and verified. Welcome to the GDE SciBOTICS Competition Management System!</p>

<div class="info-box">
    <p><strong>Your Account Details:</strong></p>
    <ul>
        <li><strong>Username:</strong> <?= htmlspecialchars($user->username) ?></li>
        <li><strong>Email:</strong> <?= htmlspecialchars($user->email) ?></li>
        <li><strong>Role:</strong> <?= htmlspecialchars($user->getRoleDisplayName()) ?></li>
    </ul>
</div>

<p>You can now access your account and start using the system:</p>

<p style="text-align: center;">
    <a href="<?= htmlspecialchars($loginUrl) ?>" class="btn">Access Your Account</a>
</p>

<h3>What's Next?</h3>

<?php if ($user->role === 'school_coordinator'): ?>
<p>As a <strong>School Coordinator</strong>, you can:</p>
<ul>
    <li>Register your school for competitions</li>
    <li>Manage team registrations</li>
    <li>View competition schedules and updates</li>
    <li>Access resources and guidelines</li>
</ul>
<?php elseif ($user->role === 'team_coach'): ?>
<p>As a <strong>Team Coach</strong>, you can:</p>
<ul>
    <li>Register and manage your teams</li>
    <li>Submit team documents and forms</li>
    <li>View competition schedules</li>
    <li>Access coaching resources</li>
</ul>
<?php endif; ?>

<div class="warning-box">
    <p><strong>Important Reminders:</strong></p>
    <ul>
        <li>Keep your login credentials secure</li>
        <li>Update your profile information as needed</li>
        <li>Check the system regularly for competition updates</li>
        <li>Contact support if you need assistance</li>
    </ul>
</div>

<p>If you have any questions or need help getting started, don't hesitate to reach out to our support team.</p>

<p>We're excited to have you participate in the GDE SciBOTICS competition!</p>

<p>Best regards,<br>
The GDE SciBOTICS Team</p>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/emails/layout.php';
?>