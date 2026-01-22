<?php

declare(strict_types=1);

/**
 * Plugs Framework Installation Controller
 * 
 * Handles all installation logic including:
 * - Server requirements checking
 * - Directory creation
 * - File generation from templates
 * - Database setup
 * - Admin account creation
 */

class InstallController
{
    private array $config;
    private array $sessionData;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->sessionData = $_SESSION['install_data'] ?? [];
    }

    /**
     * Check server requirements
     */
    public function checkRequirements(): array
    {
        $results = [
            'php_version' => [
                'name' => 'PHP Version ' . $this->config['php_version'] . '+',
                'current' => PHP_VERSION,
                'passed' => version_compare(PHP_VERSION, $this->config['php_version'], '>='),
                'required' => true,
            ],
        ];

        // Check required extensions
        foreach ($this->config['extensions'] as $ext => $name) {
            $results['ext_' . $ext] = [
                'name' => $name,
                'current' => extension_loaded($ext) ? 'Installed' : 'Not installed',
                'passed' => extension_loaded($ext),
                'required' => true,
            ];
        }

        // Check optional extensions
        foreach ($this->config['optional_extensions'] as $ext => $name) {
            $results['opt_' . $ext] = [
                'name' => $name . ' (Optional)',
                'current' => extension_loaded($ext) ? 'Installed' : 'Not installed',
                'passed' => extension_loaded($ext),
                'required' => false,
            ];
        }

        // Check directory permissions
        $results['write_root'] = [
            'name' => 'Root directory writable',
            'current' => is_writable(ROOT_PATH) ? 'Writable' : 'Not writable',
            'passed' => is_writable(ROOT_PATH),
            'required' => true,
        ];

        return $results;
    }

    /**
     * Check if all required requirements are met
     */
    public function allRequirementsMet(): bool
    {
        $requirements = $this->checkRequirements();

        foreach ($requirements as $req) {
            if ($req['required'] && !$req['passed']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Process a step submission
     */
    public function processStep(int $step, array $data): array
    {
        switch ($step) {
            case 1:
                return $this->processRequirements($data);
            case 2:
                return $this->processDatabaseConfig($data);
            case 3:
                return $this->processAppSettings($data);
            case 4:
                // Process input
                $result = $this->processAdminAccount($data);

                // If validation passed, run the installation immediately
                if ($result['success']) {
                    return $this->runInstallation();
                }

                return $result;
            case 5:
                return ['success' => true]; // Already installed if we are here via GET, but specific action handled elsewhere
            default:
                return ['success' => false, 'error' => 'Invalid step'];
        }
    }

    /**
     * Step 1: Validate requirements acceptance
     */
    private function processRequirements(array $data): array
    {
        if (!$this->allRequirementsMet()) {
            return ['success' => false, 'error' => 'Server requirements not met'];
        }

        if (empty($data['accept_license'])) {
            return ['success' => false, 'error' => 'Please accept the license agreement'];
        }

        $_SESSION['install_data']['requirements_accepted'] = true;
        return ['success' => true];
    }

    /**
     * Step 2: Process database configuration
     */
    private function processDatabaseConfig(array $data): array
    {
        $driver = $data['db_driver'] ?? 'mysql';
        $host = $data['db_host'] ?? 'localhost';
        $port = $data['db_port'] ?? ($this->config['database_types'][$driver]['default_port'] ?? 3306);
        $database = $data['db_database'] ?? '';
        $username = $data['db_username'] ?? '';
        $password = $data['db_password'] ?? '';

        // Validate required fields
        if ($driver !== 'sqlite') {
            if (empty($database)) {
                return ['success' => false, 'error' => 'Database name is required'];
            }
            if (empty($username)) {
                return ['success' => false, 'error' => 'Database username is required'];
            }
        }

        // Test connection
        $connectionResult = $this->testDatabaseConnection($driver, $host, (int) $port, $database, $username, $password);

        if (!$connectionResult['success']) {
            return $connectionResult;
        }

        // Save to session
        $_SESSION['install_data']['database'] = [
            'driver' => $driver,
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
        ];

        return ['success' => true];
    }

    /**
     * Test database connection
     */
    private function testDatabaseConnection(
        string $driver,
        string $host,
        int $port,
        string $database,
        string $username,
        string $password
    ): array {
        try {
            if ($driver === 'sqlite') {
                $dsn = 'sqlite:' . ROOT_PATH . 'storage/database.sqlite';
            } elseif ($driver === 'pgsql') {
                $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
            } else {
                $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            }

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];

            $pdo = new PDO($dsn, $username, $password, $options);

            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()];
        }
    }

    /**
     * Step 3: Process application settings
     */
    private function processAppSettings(array $data): array
    {
        $appName = trim($data['app_name'] ?? '');
        $appUrl = trim($data['app_url'] ?? '');
        $appEnv = $data['app_env'] ?? 'local';
        $appTimezone = $data['app_timezone'] ?? 'UTC';

        if (empty($appName)) {
            return ['success' => false, 'error' => 'Application name is required'];
        }

        if (empty($appUrl)) {
            return ['success' => false, 'error' => 'Application URL is required'];
        }

        // Generate app key
        $appKey = $this->generateAppKey();

        $_SESSION['install_data']['app'] = [
            'name' => $appName,
            'url' => rtrim($appUrl, '/'),
            'env' => $appEnv,
            'timezone' => $appTimezone,
            'key' => $appKey,
        ];

        return ['success' => true];
    }

    /**
     * Step 4: Process admin account
     */
    private function processAdminAccount(array $data): array
    {
        $name = trim($data['admin_name'] ?? '');
        $email = trim($data['admin_email'] ?? '');
        $password = $data['admin_password'] ?? '';
        $passwordConfirm = $data['admin_password_confirm'] ?? '';

        if (empty($name)) {
            return ['success' => false, 'error' => 'Admin name is required'];
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Valid email address is required'];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'error' => 'Password must be at least 8 characters'];
        }

        if ($password !== $passwordConfirm) {
            return ['success' => false, 'error' => 'Passwords do not match'];
        }

        $_SESSION['install_data']['admin'] = [
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ];

        return ['success' => true];
    }

    /**
     * Step 5: Run the actual installation
     */
    private function runInstallation(): array
    {
        $installData = $_SESSION['install_data'] ?? [];
        error_log("Starting installation process...");

        if (empty($installData)) {
            error_log("Installation failed: Session data is empty.");
            return ['success' => false, 'error' => 'Installation data missing from session. Please restart.'];
        }

        try {
            // 1. Create directories
            $this->createDirectories();
            error_log("Step 1: Directories created.");

            // 2. Generate files from templates
            $this->generateFiles($installData);
            error_log("Step 2: Files generated.");

            // 3. Create database tables
            $this->createDatabaseTables($installData);
            error_log("Step 3: Database tables created.");

            // 4. Create admin user
            $this->createAdminUser($installData);
            error_log("Step 4: Admin user created.");

            // 5. Create plugs.lock marker file
            $lockContent = json_encode([
                'installed' => true,
                'version' => '1.0.0',
                'framework' => 'Plugs Framework',
                'installed_at' => date('Y-m-d H:i:s'),
                'installed_by' => $installData['admin']['email'] ?? 'installer',
                'php_version' => PHP_VERSION,
                'environment' => $installData['app']['env'] ?? 'local',
                'app_name' => $installData['app']['name'] ?? 'Plugs App',
                'database_driver' => $installData['database']['driver'] ?? 'mysql',
                'checksum' => hash('sha256', serialize($installData) . time()),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            if (file_put_contents(ROOT_PATH . 'plugs.lock', $lockContent) === false) {
                error_log("Step 5: Failed to create plugs.lock at " . ROOT_PATH . 'plugs.lock');
                throw new Exception("Failed to create plugs.lock file. Check permissions.");
            }
            error_log("Step 5: plugs.lock created.");

            // 6. Installation technical steps done
            error_log("Installation technical steps completed successfully.");

            return ['success' => true];
        } catch (Exception $e) {
            error_log("Installation Exception: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create all required directories
     */
    private function createDirectories(): void
    {
        foreach ($this->config['directories'] as $dir) {
            $path = ROOT_PATH . $dir;
            if (!is_dir($path)) {
                if (!mkdir($path, 0755, true)) {
                    throw new Exception("Failed to create directory: {$dir}");
                }
            }
        }

        // Create .gitkeep files in empty directories
        $gitkeepDirs = ['storage/logs', 'storage/views', 'storage/framework', 'database/migrations'];
        foreach ($gitkeepDirs as $dir) {
            $path = ROOT_PATH . $dir . '/.gitkeep';
            if (!file_exists($path)) {
                file_put_contents($path, '');
            }
        }
    }

    /**
     * Generate files from templates
     */
    private function generateFiles(array $installData): void
    {
        foreach ($this->config['files'] as $targetFile => $templateFile) {
            $templatePath = TEMPLATES_PATH . $templateFile;
            $targetPath = ROOT_PATH . $targetFile;

            if (!file_exists($templatePath)) {
                error_log("Template not found: {$templateFile}");
                throw new Exception("Template not found: {$templateFile}");
            }

            $content = file_get_contents($templatePath);
            $content = $this->replacePlaceholders($content, $installData);

            // Create parent directory if needed
            $parentDir = dirname($targetPath);
            if (!is_dir($parentDir)) {
                if (!mkdir($parentDir, 0755, true)) {
                    error_log("Failed to create parent directory for: {$targetFile}");
                    throw new Exception("Failed to create directory: " . dirname($targetFile));
                }
            }

            if (file_put_contents($targetPath, $content) === false) {
                error_log("Failed to write to file: {$targetFile} (Path: $targetPath)");
                throw new Exception("Failed to create file: {$targetFile}. Please check permissions.");
            }

            error_log("Generated file: {$targetFile} at path: $targetPath");

            // Set executable permission for theplugs CLI
            if ($targetFile === 'theplugs') {
                @chmod($targetPath, 0755);
            }
        }
    }

    /**
     * Replace placeholders in template content
     */
    private function replacePlaceholders(string $content, array $installData): string
    {
        $replacements = [
            // App settings
            '{{APP_NAME}}' => $installData['app']['name'] ?? 'Plugs Framework',
            '{{APP_FRAMEWORK_NAME}}' => strtolower($installData['app']['name']) ?? 'plugs',
            '{{APP_URL}}' => $installData['app']['url'] ?? 'http://localhost',
            '{{APP_ENV}}' => $installData['app']['env'] ?? 'local',
            '{{APP_TIMEZONE}}' => $installData['app']['timezone'] ?? 'UTC',
            '{{APP_KEY}}' => $installData['app']['key'] ?? $this->generateAppKey(),
            '{{APP_DEBUG}}' => ($installData['app']['env'] ?? 'local') === 'local' ? 'true' : 'false',

            // Database settings
            '{{DB_DRIVER}}' => $installData['database']['driver'] ?? 'mysql',
            '{{DB_HOST}}' => $installData['database']['host'] ?? 'localhost',
            '{{DB_PORT}}' => (string) ($installData['database']['port'] ?? 3306),
            '{{DB_DATABASE}}' => $installData['database']['database'] ?? 'plugs',
            '{{DB_USERNAME}}' => $installData['database']['username'] ?? 'root',
            '{{DB_PASSWORD}}' => $installData['database']['password'] ?? '',

            // Generated values
            '{{GENERATED_DATE}}' => date('Y-m-d H:i:s'),
            '{{YEAR}}' => date('Y'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Create database tables
     */
    private function createDatabaseTables(array $installData): void
    {
        $db = $installData['database'];

        try {
            if ($db['driver'] === 'sqlite') {
                $dsn = 'sqlite:' . ROOT_PATH . 'storage/database.sqlite';
                $pdo = new PDO($dsn);
            } elseif ($db['driver'] === 'pgsql') {
                $dsn = "pgsql:host={$db['host']};port={$db['port']};dbname={$db['database']}";
                $pdo = new PDO($dsn, $db['username'], $db['password']);
            } else {
                $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset=utf8mb4";
                $pdo = new PDO($dsn, $db['username'], $db['password']);
            }

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Create users table
            $this->createUsersTable($pdo, $db['driver']);

            // Create sessions table
            $this->createSessionsTable($pdo, $db['driver']);

            // Create migrations table
            $this->createMigrationsTable($pdo, $db['driver']);

            // Create jobs table
            $this->createJobsTable($pdo, $db['driver']);

        } catch (PDOException $e) {
            throw new Exception('Database setup failed: ' . $e->getMessage());
        }
    }

    /**
     * Create users table
     */
    private function createUsersTable(PDO $pdo, string $driver): void
    {
        if ($driver === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                role TEXT DEFAULT 'user',
                email_verified_at TEXT NULL,
                remember_token TEXT NULL,
                last_login_at TEXT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )";
        } elseif ($driver === 'pgsql') {
            $sql = "CREATE TABLE IF NOT EXISTS users (
                id SERIAL PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(50) DEFAULT 'user',
                email_verified_at TIMESTAMP NULL,
                remember_token VARCHAR(100) NULL,
                last_login_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS users (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(50) DEFAULT 'user',
                email_verified_at TIMESTAMP NULL,
                remember_token VARCHAR(100) NULL,
                last_login_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }

        $pdo->exec($sql);
    }

    /**
     * Create sessions table
     */
    private function createSessionsTable(PDO $pdo, string $driver): void
    {
        if ($driver === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS sessions (
                id TEXT PRIMARY KEY,
                user_id INTEGER NULL,
                ip_address TEXT NULL,
                user_agent TEXT NULL,
                payload TEXT NOT NULL,
                last_activity INTEGER NOT NULL
            )";
        } elseif ($driver === 'pgsql') {
            $sql = "CREATE TABLE IF NOT EXISTS sessions (
                id VARCHAR(255) PRIMARY KEY,
                user_id BIGINT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                payload TEXT NOT NULL,
                last_activity INTEGER NOT NULL
            )";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS sessions (
                id VARCHAR(255) PRIMARY KEY,
                user_id BIGINT UNSIGNED NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                payload LONGTEXT NOT NULL,
                last_activity INT NOT NULL,
                INDEX sessions_user_id_index (user_id),
                INDEX sessions_last_activity_index (last_activity)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }

        $pdo->exec($sql);
    }

    /**
     * Create migrations table
     */
    private function createMigrationsTable(PDO $pdo, string $driver): void
    {
        if ($driver === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration TEXT NOT NULL,
                batch INTEGER NOT NULL
            )";
        } elseif ($driver === 'pgsql') {
            $sql = "CREATE TABLE IF NOT EXISTS migrations (
                id SERIAL PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INTEGER NOT NULL
            )";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS migrations (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }

        $pdo->exec($sql);
    }

    /**
     * Create jobs table
     */
    private function createJobsTable(PDO $pdo, string $driver): void
    {
        if ($driver === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS jobs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                queue TEXT NOT NULL,
                payload TEXT NOT NULL,
                attempts INTEGER DEFAULT 0,
                reserved_at INTEGER NULL,
                available_at INTEGER NOT NULL,
                created_at INTEGER NOT NULL
            )";
        } elseif ($driver === 'pgsql') {
            $sql = "CREATE TABLE IF NOT EXISTS jobs (
                id SERIAL PRIMARY KEY,
                queue VARCHAR(255) NOT NULL,
                payload TEXT NOT NULL,
                attempts SMALLINT DEFAULT 0,
                reserved_at INTEGER NULL,
                available_at INTEGER NOT NULL,
                created_at INTEGER NOT NULL
            )";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS jobs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                queue VARCHAR(255) NOT NULL,
                payload LONGTEXT NOT NULL,
                attempts TINYINT UNSIGNED DEFAULT 0,
                reserved_at INT UNSIGNED NULL,
                available_at INT UNSIGNED NOT NULL,
                created_at INT UNSIGNED NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }

        $pdo->exec($sql);
    }

    /**
     * Create admin user
     */
    private function createAdminUser(array $installData): void
    {
        $admin = $installData['admin'];
        $db = $installData['database'];

        try {
            if ($db['driver'] === 'sqlite') {
                $dsn = 'sqlite:' . ROOT_PATH . 'storage/database.sqlite';
                $pdo = new PDO($dsn);
            } elseif ($db['driver'] === 'pgsql') {
                $dsn = "pgsql:host={$db['host']};port={$db['port']};dbname={$db['database']}";
                $pdo = new PDO($dsn, $db['username'], $db['password']);
            } else {
                $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['database']};charset=utf8mb4";
                $pdo = new PDO($dsn, $db['username'], $db['password']);
            }

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, 'admin', ?)");
            $stmt->execute([
                $admin['name'],
                $admin['email'],
                $admin['password'],
                date('Y-m-d H:i:s'),
            ]);

        } catch (PDOException $e) {
            throw new Exception('Failed to create admin user: ' . $e->getMessage());
        }
    }

    /**
     * Generate a random application key
     */
    private function generateAppKey(): string
    {
        return 'base64:' . base64_encode(random_bytes(32));
    }

    /**
     * Get data for a specific step
     */
    public function getStepData(int $step): array
    {
        return [
            'step' => $step,
            'total_steps' => 5,
            'session_data' => $_SESSION['install_data'] ?? [],
            'config' => $this->config,
        ];
    }

    /**
     * Attempt to run composer install
     */
    public function installComposer(): array
    {
        $composerPath = $this->findComposer();

        if (!$composerPath) {
            return ['success' => false, 'error' => 'Composer not found in system PATH.'];
        }

        $command = "cd " . escapeshellarg(ROOT_PATH) . " && $composerPath install 2>&1";

        // Execute command
        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            return [
                'success' => false,
                'error' => 'Composer install failed.',
                'details' => implode("\n", $output)
            ];
        }

        return ['success' => true, 'message' => 'Composer dependencies installed successfully!'];
    }

    /**
     * Find composer executable
     */
    private function findComposer(): ?string
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $output = [];
            exec('where composer', $output);
            return !empty($output) ? 'composer' : null;
        } else {
            $output = [];
            exec('which composer', $output);
            return !empty($output) ? 'composer' : null;
        }
    }

    /**
     * Cleanup installation files
     */
    public function cleanupInstallation(): array
    {
        // On Windows, we cannot delete the directory we are running from due to file locking.
        // We will generate a cleanup script in the root directory that deletes the install folder
        // and then deletes itself.

        $cleanupScript = <<<'PHP'
<?php
/**
 * Plugs Installer Cleanup Script
 * Automatically generated to bypass file locking issues.
 */

// Wait a moment for the installer process to finish
sleep(1);

$publicDir = __DIR__;
$installDir = $publicDir . '/install';
$rootDir = dirname($publicDir);

function recursiveDelete($dir) {
    if (!is_dir($dir)) return;
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            recursiveDelete($path);
        } else {
            @chmod($path, 0666);
            @unlink($path);
        }
    }
    @rmdir($dir);
}

// Log start
file_put_contents($rootDir . '/cleanup.log', "Cleanup started at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

if (is_dir($installDir)) {
    recursiveDelete($installDir);
    file_put_contents($rootDir . '/cleanup.log', "Deleted $installDir\n", FILE_APPEND);
} else {
    file_put_contents($rootDir . '/cleanup.log', "Install dir not found: $installDir\n", FILE_APPEND);
}

// Delete this script
@unlink(__FILE__);
file_put_contents($rootDir . '/cleanup.log', "Cleanup script deleted itself. Finished.\n", FILE_APPEND);


// Redirect to home
header("Location: /");
exit;
PHP;

        $scriptPath = ROOT_PATH . 'public/cleanup_installer.php';
        $appUrl = $_SESSION['install_data']['app']['url'] ?? '';

        // Ensure redirect URL points to the cleanup script in public folder
        $redirectUrl = !empty($appUrl) ? rtrim($appUrl, '/') . '/cleanup_installer.php' : '/cleanup_installer.php';


        if (file_put_contents($scriptPath, $cleanupScript) !== false) {
            return ['success' => true, 'redirect' => $redirectUrl];
        }

        // Fallback to internal delete attempt (likely fails on Windows)
        try {
            $this->recursiveDelete(INSTALL_PATH);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Could not delete files automatically. Please delete the "install" folder manually.'];
        }
    }

    /**
     * Recursive delete
     */
    private function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;

            try {
                if (is_dir($path)) {
                    $this->recursiveDelete($path);
                } else {
                    @unlink($path);
                }
            } catch (Exception $e) {
                // Ignore errors for locking
            }
        }

        @rmdir($dir);
    }
}
