<div class="step-content">
    <div class="animate-in">
        <h2 class="step-title">Welcome to Plugs</h2>
        <p class="step-description">Let's ensure your environment is primed for high-performance PHP development.</p>
    </div>

    <!-- Requirements Section -->
    <div class="animate-in stagger-1">
        <h3
            style="font-size: 1.1rem; margin-bottom: 1.25rem; color: var(--pl-primary); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">
            <i class="fas fa-server me-2"></i> Server Pulse Check
        </h3>

        <div class="requirements-grid">
            <?php
            $allPassed = true;
            foreach ($requirements as $key => $req):
                if ($req['required'] && !$req['passed']) {
                    $allPassed = false;
                }
                $statusClass = $req['passed'] ? 'status-passed' : ($req['required'] ? 'status-failed' : 'status-optional');
                $statusIcon = $req['passed'] ? 'fa-check-circle' : ($req['required'] ? 'fa-times-circle' : 'fa-exclamation-circle');
                ?>
                <div class="requirement-item">
                    <div class="requirement-info">
                        <span class="requirement-name"><?= htmlspecialchars($req['name']) ?></span>
                        <span
                            class="requirement-detail"><?= $req['passed'] ? 'Currently: ' . htmlspecialchars($req['current']) : ($req['required'] ? 'Setup Required' : 'Advanced Feature') ?></span>
                    </div>
                    <div class="requirement-status <?= $statusClass ?>">
                        <i class="fas <?= $statusIcon ?> me-1"></i>
                        <?= $req['passed'] ? 'Pass' : ($req['required'] ? 'Fail' : 'Opt') ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (!$allPassed): ?>
        <div class="alert alert-error animate-in stagger-2">
            <i class="fas fa-microchip-slash fa-lg"></i>
            <div>
                <strong>Incompatible Environment</strong><br>
                Please resolve the missing requirements above to unlock the Plugs Framework.
            </div>
        </div>
    <?php endif; ?>

    <!-- License Section -->
    <div class="animate-in stagger-2" style="margin-top: 2.5rem;">
        <h3
            style="font-size: 1.1rem; margin-bottom: 1.25rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">
            <i class="fas fa-file-contract me-2"></i> Open Source License
        </h3>
        <div class="license-box">
            <div style="color: var(--pl-primary); font-weight: 700; margin-bottom: 1rem;">Apache License 2.0</div>
            <p style="margin-bottom: 1rem; opacity: 0.8;">Copyright <?= date('Y') ?> Plugs Framework (Celio Natti)</p>
            <p style="margin-bottom: 1rem; opacity: 0.7; font-size: 0.9em;">
                Licensed under the Apache License, Version 2.0. You may use, modify, and distribute this framework for
                both personal and commercial projects, provided you maintain the original copyright notice.
            </p>
            <ul style="list-style: none; padding-left: 0; opacity: 0.8; font-size: 0.9em;">
                <li style="margin-bottom: 0.5rem;"><i class="fas fa-check-circle text-success me-2"></i> Commercial Use
                    Allowed</li>
                <li style="margin-bottom: 0.5rem;"><i class="fas fa-check-circle text-success me-2"></i> Modification
                    Allowed</li>
                <li style="margin-bottom: 0.5rem;"><i class="fas fa-check-circle text-success me-2"></i> Redistribution
                    Allowed</li>
                <li><i class="fas fa-info-circle text-warning me-2"></i> Provided "as is" without warranty</li>
            </ul>
        </div>
    </div>

    <form method="post" action="?step=1" class="animate-in stagger-3">
        <label class="custom-checkbox">
            <input type="checkbox" name="accept_license" value="1"
                style="position: absolute; opacity: 0; pointer-events: none;" required <?= !$allPassed ? 'disabled' : '' ?>>
            <div class="checkbox-toggle">
                <i class="fas fa-check"></i>
            </div>
            <div class="checkbox-label">
                I have reviewed the <a href="https://www.apache.org/licenses/LICENSE-2.0" target="_blank"
                    style="color: var(--pl-primary); font-weight: 600;">Apache License 2.0</a> and agree to its terms.
            </div>
        </label>

        <div class="btn-group">
            <button type="submit" class="btn btn-primary" <?= !$allPassed ? 'disabled' : '' ?>>
                Next: Connect Database <i class="fas fa-arrow-right ms-2"></i>
            </button>
        </div>
    </form>
</div>