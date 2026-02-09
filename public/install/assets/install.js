/**
 * Plugs Framework Installer - Enhanced Interactivity
 */

document.addEventListener('DOMContentLoaded', function () {
    // Initialize all features
    initPasswordStrength();
    initDatabaseTypeChange();
    initTestConnection();
    initFormInteractions();
    initCopyButtons();
});

/**
 * Password Strength Indicator with Premium Feedback
 */
function initPasswordStrength() {
    const passwordInput = document.getElementById('admin_password');
    const strengthBar = document.querySelector('.password-strength-fill');

    if (!passwordInput || !strengthBar) return;

    passwordInput.addEventListener('input', function () {
        const password = this.value;
        const result = calculatePasswordStrength(password);
        const strength = result.score;

        strengthBar.className = 'password-strength-fill';

        if (password.length === 0) {
            strengthBar.style.width = '0';
        } else {
            strengthBar.style.width = (strength * 20) + '%';
            if (strength <= 2) strengthBar.classList.add('weak');
            else if (strength <= 4) strengthBar.classList.add('fair');
            else if (strength <= 6) strengthBar.classList.add('good');
            else strengthBar.classList.add('strong');
        }
    });
}

function calculatePasswordStrength(password) {
    let score = 0;
    if (password.length >= 8) score += 2;
    if (password.length >= 12) score += 2;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score += 1;
    if (/\d/.test(password)) score += 1;
    if (/[^a-zA-Z0-9]/.test(password)) score += 2;

    return { score: Math.min(score, 10) };
}

/**
 * Database Type Change Handler
 */
function initDatabaseTypeChange() {
    const dbTypeSelect = document.getElementById('db_driver');
    const dbHostGroup = document.getElementById('db-host-group');
    const dbPortGroup = document.getElementById('db-port-group');
    const dbUsernameGroup = document.getElementById('db-username-group');
    const dbPasswordGroup = document.getElementById('db-password-group');
    const dbDatabaseGroup = document.getElementById('db-database-group');
    const portInput = document.getElementById('db_port');

    if (!dbTypeSelect) return;

    const defaultPorts = {
        'mysql': 3306,
        'pgsql': 5432,
        'sqlite': null
    };

    dbTypeSelect.addEventListener('change', function () {
        const driver = this.value;
        const isSqlite = driver === 'sqlite';

        // Animate visibility changes
        [dbHostGroup, dbPortGroup, dbUsernameGroup, dbPasswordGroup].forEach(el => {
            if (el) {
                if (isSqlite) {
                    el.style.opacity = '0';
                    setTimeout(() => el.style.display = 'none', 300);
                } else {
                    el.style.display = '';
                    setTimeout(() => el.style.opacity = '1', 10);
                }
            }
        });

        // Update database label for SQLite
        if (dbDatabaseGroup) {
            const label = dbDatabaseGroup.querySelector('.form-label');
            const hint = dbDatabaseGroup.querySelector('.form-hint');
            if (isSqlite && label) {
                label.textContent = 'SQLite Database Path';
            } else if (label) {
                label.textContent = 'Database / Schema Name';
            }
        }

        if (portInput && defaultPorts[driver]) {
            portInput.value = defaultPorts[driver];
        }
    });
}

/**
 * Test Database Connection with Premium Loading State
 */
function initTestConnection() {
    const testBtn = document.getElementById('test-connection-btn');
    const statusEl = document.getElementById('connection-status');

    if (!testBtn) return;

    testBtn.addEventListener('click', async function (e) {
        e.preventDefault();

        const originalHTML = testBtn.innerHTML;
        testBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Analyzing Connection...';
        testBtn.disabled = true;

        statusEl.className = 'connection-status animate-in';
        statusEl.innerHTML = '<i class="fas fa-network-wired me-2"></i> Pinging database server...';

        const formData = new FormData();
        formData.append('db_driver', document.getElementById('db_driver')?.value || 'mysql');
        formData.append('db_host', document.getElementById('db_host')?.value || 'localhost');
        formData.append('db_port', document.getElementById('db_port')?.value || '3306');
        formData.append('db_database', document.getElementById('db_database')?.value || '');
        formData.append('db_username', document.getElementById('db_username')?.value || '');
        formData.append('db_password', document.getElementById('db_password')?.value || '');

        try {
            const response = await fetch('test-connection.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (statusEl) {
                if (result.success) {
                    statusEl.className = 'connection-status animate-in text-success';
                    statusEl.innerHTML = '<i class="fas fa-check-double me-2"></i> Connection handshake successful!';
                } else {
                    statusEl.className = 'connection-status animate-in text-error';
                    statusEl.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i> ' + (result.error || 'Connection failed');
                }
            }
        } catch (error) {
            if (statusEl) {
                statusEl.className = 'connection-status animate-in text-error';
                statusEl.innerHTML = '<i class="fas fa-wifi-slash me-2"></i> Network propagation fault';
            }
        }

        testBtn.innerHTML = originalHTML;
        testBtn.disabled = false;
    });
}

/**
 * Form Interactions and Loading States
 */
function initFormInteractions() {
    const forms = document.querySelectorAll('form');

    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            const submitBtn = form.querySelector('button[type="submit"]');

            if (submitBtn && !submitBtn.disabled) {
                const originalHTML = submitBtn.innerHTML;

                // Use setTimeout to allow the submit event to propagate before disabling
                setTimeout(() => {
                    submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin me-2"></i> Processing Request...';
                    submitBtn.disabled = true;
                }, 0);

                // Recovery in case of massive lag
                setTimeout(() => {
                    if (submitBtn.disabled) {
                        submitBtn.innerHTML = originalHTML;
                        submitBtn.disabled = false;
                    }
                }, 15000);
            }
        });
    });

    // Custom Checkbox Trigger
    const checkboxes = document.querySelectorAll('.custom-checkbox');
    checkboxes.forEach(label => {
        const input = label.querySelector('input[type="checkbox"]');
        if (input) {
            input.addEventListener('change', () => {
                label.style.borderColor = input.checked ? 'var(--pl-primary)' : 'var(--glass-border)';
            });
        }
    });
}

/**
 * Copy to Clipboard with Premium Notification
 */
function initCopyButtons() {
    // Shared utility for the success page
    window.copyText = function (text) {
        navigator.clipboard.writeText(text).then(() => {
            const toast = document.createElement('div');
            toast.className = 'alert animate-in';
            toast.style.cssText = `
                position: fixed; top: 2rem; right: 2rem; z-index: 10000;
                background: var(--pl-primary); color: var(--bg-body);
                padding: 1rem 1.5rem; border-radius: 12px; font-weight: 700;
                box-shadow: 0 10px 30px oklch(0% 0 0 / 0.3);
            `;
            toast.innerHTML = '<i class="fas fa-clipboard-check me-2"></i> Securely Copied!';
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(20px)';
                setTimeout(() => toast.remove(), 400);
            }, 2500);
        });
    };
}
