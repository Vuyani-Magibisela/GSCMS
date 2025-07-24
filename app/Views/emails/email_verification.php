<h2>Verify Your Email Address</h2>

<p>Hello <?= htmlspecialchars($user->getDisplayName()) ?>,</p>

<p>Welcome to the GDE SciBOTICS Competition Management System! To complete your registration and activate your account, please verify your email address.</p>

<p style="text-align: center;">
    <a href="<?= htmlspecialchars($verifyUrl) ?>" class="btn">Verify Email Address</a>
</p>

<div class="info-box">
    <p><strong>Why do we need to verify your email?</strong></p>
    <ul>
        <li>To ensure we can send you important competition updates</li>
        <li>To secure your account and prevent unauthorized access</li>
        <li>To enable password recovery if needed</li>
    </ul>
</div>

<p>If the button above doesn't work, copy and paste the following link into your browser:</p>
<p style="word-break: break-all; background-color: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px;">
    <?= htmlspecialchars($verifyUrl) ?>
</p>

<div class="warning-box">
    <p><strong>Important:</strong></p>
    <p>Your account will remain inactive until you verify your email address. You won't be able to log in or access the system until verification is complete.</p>
</div>

<p>If you didn't create an account with us, please ignore this email. The account will be automatically deleted after 7 days if not verified.</p>

<p>Best regards,<br>
The GDE SciBOTICS Team</p>