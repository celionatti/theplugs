<?php
$appName = $stepData['session_data']['app']['name'] ?? 'Plugs Framework';
$appUrl = $stepData['session_data']['app']['url'] ?? '';
?>
<div class="step-content">
    <div class="success-hero animate-in">
        <div class="success-orb">
            <i class="fas fa-check"></i>
        </div>
        <h2 class="success-title">System Fully Operational</h2>
        <p class="success-message">
            Congratulations! <strong><?= htmlspecialchars($appName) ?></strong> has been successfully deployed and is
            ready for development.
        </p>
    </div>

    <div class="alert animate-in stagger-1"
        style="background: oklch(65% 0.2 25 / 0.1); border: 1px solid oklch(65% 0.2 25 / 0.2); color: var(--error); margin-bottom: 2rem;">
        <i class="fas fa-shield-virus fa-lg"></i>
        <div>
            <strong>Critical Security Action</strong><br>
            Please remove the <code>install</code> directory from your project root to prevent unauthorized
            re-configuration.
        </div>
    </div>

    <div class="form-section animate-in stagger-2" style="text-align: left;">
        <h3
            style="font-size: 1.1rem; margin-bottom: 1.25rem; color: var(--pl-primary); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">
            <i class="fas fa-clipboard-list me-2"></i> Deployment Summary
        </h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
            <div style="opacity: 0.8; font-size: 0.95rem;"><i class="fas fa-check text-success me-2"></i> File Hierarchy
                Created</div>
            <div style="opacity: 0.8; font-size: 0.95rem;"><i class="fas fa-check text-success me-2"></i> Schema
                Migrations Done</div>
            <div style="opacity: 0.8; font-size: 0.95rem;"><i class="fas fa-check text-success me-2"></i> Admin
                Privilege Granted</div>
            <div style="opacity: 0.8; font-size: 0.95rem;"><i class="fas fa-check text-success me-2"></i> Env
                Configuration Active</div>
        </div>

        <div style="padding-top: 1.25rem; border-top: 1px solid oklch(100% 0 0 / 0.05);">
            <p style="color: var(--text-muted); font-weight: 600; margin-bottom: 0.75rem;">Next Milestones:</p>
            <ol style="color: var(--text-dim); font-size: 0.9rem; padding-left: 1.25rem; line-height: 1.8;">
                <li>Execute secure cleanup with the button below</li>
                <li>Verify your installation in the <a href="https://github.com/celionatti/plugs" target="_blank"
                        style="color: var(--pl-primary);">Plugs Documentation</a></li>
                <li>Begin building your masterpiece!</li>
            </ol>
        </div>
    </div>

    <div class="btn-group animate-in stagger-3" style="flex-direction: column; gap: 1rem;">
        <div style="display: flex; gap: 1rem;">
            <button type="button" class="btn btn-primary" id="btn-cleanup"
                style="flex: 2; font-weight: 800; font-size: 1.1rem;">
                <i class="fas fa-rocket me-2"></i> Finalize & Launch App
            </button>
            <button type="button" class="btn btn-secondary" id="btn-composer" style="flex: 1;">
                <i class="fas fa-box me-2"></i> Sync Deps
            </button>
        </div>
        <a href="<?= htmlspecialchars($appUrl) ?>/" class="btn btn-secondary"
            style="opacity: 0.5; font-size: 0.85rem; padding: 0.5rem;">
            <i class="fas fa-external-link-alt me-2"></i> Navigate to Homepage (Skip Cleanup)
        </a>
    </div>

    <div id="action-status" class="animate-in stagger-3"
        style="margin-top: 1.5rem; text-align: center; color: var(--text-dim); min-height: 24px;"></div>
</div>

<script>
    document.getElementById('btn-composer').addEventListener('click', async function () {
        const btn = this;
        const status = document.getElementById('action-status');
        btn.disabled = true;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Syncing...';
        status.innerHTML = '<span class="animate-in">Initializing Composer sync... Please wait.</span>';

        try {
            const response = await fetch('?action=composer', { method: 'POST' });
            const data = await response.json();

            if (data.success) {
                btn.innerHTML = '<i class="fas fa-check-circle me-2"></i> Synced';
                btn.style.color = 'var(--success)';
                btn.style.borderColor = 'var(--success)';
                status.innerHTML = '<span class="animate-in">' + data.message + '</span>';
            } else {
                btn.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i> Failed';
                btn.disabled = false;
                status.innerHTML = '<span class="animate-in text-error">Error: ' + (data.error || 'Sync failed') + '</span>';
                if (data.details) {
                    console.error(data.details);
                    alert('Composer Output:\n' + data.details);
                }
            }
        } catch (e) {
            btn.innerHTML = '<i class="fas fa-wifi-slash me-2"></i> Network Error';
            btn.disabled = false;
            status.innerHTML = '<span class="animate-in text-error">Communication failed.</span>';
        }
    });

    document.getElementById('btn-cleanup').addEventListener('click', async function () {
        const btn = this;
        const originalText = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin me-2"></i> Optimizing & Cleaning...';

        try {
            const response = await fetch('?action=cleanup', { method: 'POST' });
            const data = await response.json();

            if (data.success) {
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    window.location.href = '<?= htmlspecialchars($appUrl) ?>/';
                }
            } else {
                alert(data.error || 'Cleanup halted. Please perform manually.');
                window.location.href = '<?= htmlspecialchars($appUrl) ?>/';
            }
        } catch (e) {
            window.location.href = '<?= htmlspecialchars($appUrl) ?>/';
        }
    });
</script>