<?php
$content = ob_get_clean();
ob_start();
?>

<h2>Password Reset Request</h2>

<p>Hello <?= htmlspecialchars($user->getDisplayName()) ?>,</p>

<p>We received a request to reset the password for your GDE SciBOTICS account associated with <strong><?= htmlspecialchars($user->email) ?></strong>.</p>

<p>To reset your password, click the button below:</p>

<p style="text-align: center;">
    <a href="<?= htmlspecialchars($resetUrl) ?>" class="btn">Reset My Password</a>
</p>

<div class="warning-box">
    <p><strong>Important Security Information:</strong></p>
    <ul>
        <li>This link will expire in <strong><?= $expiresIn ?></strong></li>
        <li>If you did not request this password reset, please ignore this email</li>
        <li>Your password will remain unchanged until you create a new one</li>
    </ul>
</div>

<p>If the button above doesn't work, copy and paste the following link into your browser:</p>
<p style="word-break: break-all; background-color: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px;">
    <?= htmlspecialchars($resetUrl) ?>
</p>

<div class="info-box">
    <p><strong>Need Help?</strong></p>
    <p>If you're having trouble resetting your password or didn't request this change, please contact our support team at <a href="mailto:support@gscms.com">support@gscms.com</a>.</p>
</div>

<p>Best regards,<br>
The GDE SciBOTICS Team</p>

<?php
$content = ob_get_clean();
include VIEW_PATH . '/emails/layout.php';
?>