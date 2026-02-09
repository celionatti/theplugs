<?php
$timezones = $stepData['config']['timezones'] ?? [];
$sessionApp = $stepData['session_data']['app'] ?? [];

// Try to detect the current URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$defaultUrl = $protocol . '://' . $host;
// Remove /install from URL if present
$defaultUrl = preg_replace('#/install/?$#', '', $defaultUrl);
?>
<div class="step-content">
    <div class="animate-in">
        <h2 class="step-title">Application Identity</h2>
        <p class="step-description">Define how the world sees and interacts with your Plugs application.</p>
    </div>

    <form method="post" action="?step=3">
        <div class="form-section animate-in stagger-1">
            <div class="form-group">
                <label for="app_name" class="form-label">Application Name</label>
                <input type="text" name="app_name" id="app_name" class="form-input"
                    value="<?= htmlspecialchars($sessionApp['name'] ?? 'My Plugs App') ?>"
                    placeholder="e.g., Plugs Commerce" required>
                <div class="form-hint">Displayed in browser titles, emails, and system logs.</div>
            </div>

            <div class="form-group">
                <label for="app_url" class="form-label">Canonical URL</label>
                <input type="url" name="app_url" id="app_url" class="form-input"
                    value="<?= htmlspecialchars($sessionApp['url'] ?? $defaultUrl) ?>"
                    placeholder="https://plugs.framework" required>
                <div class="form-hint">The base URL of your site. Do not include a trailing slash.</div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="app_env" class="form-label">Current Environment</label>
                    <div class="input-wrapper">
                        <select name="app_env" id="app_env" class="form-select" required>
                            <option value="local" <?= ($sessionApp['env'] ?? 'local') === 'local' ? 'selected' : '' ?>>
                                Local (Dev Mode)
                            </option>
                            <option value="staging" <?= ($sessionApp['env'] ?? '') === 'staging' ? 'selected' : '' ?>>
                                Staging (UAT)
                            </option>
                            <option value="production" <?= ($sessionApp['env'] ?? '') === 'production' ? 'selected' : '' ?>>
                                Production (Live)
                            </option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="app_timezone" class="form-label">System Timezone</label>
                    <div class="input-wrapper">
                        <select name="app_timezone" id="app_timezone" class="form-select" required>
                            <?php foreach ($timezones as $tz => $label): ?>
                                <option value="<?= $tz ?>" <?= ($sessionApp['timezone'] ?? 'UTC') === $tz ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert animate-in stagger-2"
            style="background: var(--pl-primary-low); border: 1px solid var(--pl-primary-mid); color: var(--pl-primary);">
            <i class="fas fa-shield-alt fa-lg"></i>
            <div>
                <strong>Security First</strong><br>
                Plugs will automatically generate a unique 256-bit encryption key for your application.
            </div>
        </div>

        <div class="btn-group animate-in stagger-3">
            <a href="?step=2" class="btn btn-secondary">
                <i class="fas fa-chevron-left me-2"></i> Back
            </a>
            <button type="submit" class="btn btn-primary">
                Next: Create Admin Account <i class="fas fa-chevron-right ms-2"></i>
            </button>
        </div>
    </form>
</div>