<?php
$sessionAdmin = $stepData['session_data']['admin'] ?? [];
?>
<div class="step-content">
    <div class="animate-in">
        <h2 class="step-title">Master Administrator</h2>
        <p class="step-description">Establish the primary authority for your new Plugs application.</p>
    </div>

    <form method="post" action="?step=4">
        <div class="form-section animate-in stagger-1">
            <div class="form-group">
                <label for="admin_name" class="form-label">Full Name</label>
                <div class="input-wrapper">
                    <input type="text" name="admin_name" id="admin_name" class="form-input"
                        value="<?= htmlspecialchars($sessionAdmin['name'] ?? '') ?>" placeholder="e.g., John Doe"
                        required>
                </div>
            </div>

            <div class="form-group">
                <label for="admin_email" class="form-label">Administrative Email</label>
                <div class="input-wrapper">
                    <input type="email" name="admin_email" id="admin_email" class="form-input"
                        value="<?= htmlspecialchars($sessionAdmin['email'] ?? '') ?>" placeholder="admin@yourdomain.com"
                        required>
                </div>
                <div class="form-hint">This email will serve as your primary login identifier.</div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="admin_password" class="form-label">Secure Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="admin_password" id="admin_password" class="form-input"
                            placeholder="••••••••" minlength="8" required>
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-fill"></div>
                    </div>
                    <div class="form-hint">At least 8 characters. Mix case, numbers, and symbols for maximum security.
                    </div>
                </div>

                <div class="form-group">
                    <label for="admin_password_confirm" class="form-label">Verify Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="admin_password_confirm" id="admin_password_confirm"
                            class="form-input" placeholder="••••••••" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert animate-in stagger-2"
            style="background: oklch(80% 0.15 70 / 0.1); border: 1px solid oklch(80% 0.15 70 / 0.2); color: var(--warning);">
            <i class="fas fa-lightbulb fa-lg"></i>
            <div>
                <strong>Identity Note</strong><br>
                Ensure these credentials are kept secure. They grant full access to your application core.
            </div>
        </div>

        <div class="btn-group animate-in stagger-3">
            <a href="?step=3" class="btn btn-secondary">
                <i class="fas fa-chevron-left me-2"></i> Back
            </a>
            <button type="submit" class="btn btn-primary"
                style="background: linear-gradient(135deg, var(--pl-primary), var(--pl-accent));">
                <i class="fas fa-magic me-2"></i> Begin Full Installation
            </button>
        </div>
    </form>
</div>