<?php
$dbTypes = $stepData['config']['database_types'] ?? [];
$sessionDb = $stepData['session_data']['database'] ?? [];
?>
<div class="step-content">
    <div class="animate-in">
        <h2 class="step-title">Database Engine</h2>
        <p class="step-description">Connect your application to its digital memory. We support modern SQL engines.</p>
    </div>

    <form method="post" action="?step=2">
        <div class="form-section animate-in stagger-1">
            <div class="form-group">
                <label for="db_driver" class="form-label">Database Driver</label>
                <div class="input-wrapper">
                    <select name="db_driver" id="db_driver" class="form-select" required>
                        <?php foreach ($dbTypes as $driver => $info): ?>
                            <option value="<?= $driver ?>" <?= ($sessionDb['driver'] ?? 'mysql') === $driver ? 'selected' : '' ?>>
                                <?= htmlspecialchars($info['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid-2" id="db-host-group">
                <div class="form-group">
                    <label for="db_host" class="form-label">Host Address</label>
                    <input type="text" name="db_host" id="db_host" class="form-input"
                        value="<?= htmlspecialchars($sessionDb['host'] ?? 'localhost') ?>" placeholder="e.g., localhost or 127.0.0.1">
                </div>
                <div class="form-group" id="db-port-group">
                    <label for="db_port" class="form-label">Network Port</label>
                    <input type="number" name="db_port" id="db_port" class="form-input"
                        value="<?= htmlspecialchars($sessionDb['port'] ?? '3306') ?>" placeholder="3306">
                </div>
            </div>

            <div class="form-group" id="db-database-group">
                <label for="db_database" class="form-label">Database / Schema Name</label>
                <input type="text" name="db_database" id="db_database" class="form-input"
                    value="<?= htmlspecialchars($sessionDb['database'] ?? '') ?>" placeholder="my_application_db" required>
                <p class="form-hint">Ensure the database exists on your server before proceeding.</p>
            </div>

            <div class="grid-2">
                <div class="form-group" id="db-username-group">
                    <label for="db_username" class="form-label">Username</label>
                    <input type="text" name="db_username" id="db_username" class="form-input"
                        value="<?= htmlspecialchars($sessionDb['username'] ?? 'root') ?>" placeholder="root">
                </div>
                <div class="form-group" id="db-password-group">
                    <label for="db_password" class="form-label">Security Password</label>
                    <input type="password" name="db_password" id="db_password" class="form-input"
                        value="<?= htmlspecialchars($sessionDb['password'] ?? '') ?>" placeholder="••••••••">
                </div>
            </div>
        </div>

        <div class="animate-in stagger-2">
            <button type="button" id="test-connection-btn" class="test-connection-btn">
                <i class="fas fa-plug me-2"></i> Validate Connection
            </button>
            <div id="connection-status" class="connection-status" style="margin-bottom: 2rem;"></div>
        </div>

        <div class="btn-group animate-in stagger-3">
            <a href="?step=1" class="btn btn-secondary">
                <i class="fas fa-chevron-left me-2"></i> Back
            </a>
            <button type="submit" class="btn btn-primary">
                Next Step: Application Settings <i class="fas fa-chevron-right ms-2"></i>
            </button>
        </div>
    </form>
</div>