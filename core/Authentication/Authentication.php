<?php

declare(strict_types=1);

namespace Plugs\Authentication;

use PDO;
use DateTime;
use Plugs\Session\SessionManager;
use Plugs\Illusion\Helper\JwtManager;
use Plugs\Authentication\Interface\UserInterface;

class Authentication
{
    private PDO $db;
    private AuthConfig $config;
    private ?UserInterface $currentUser = null;
    private SessionManager $session;
    private JwtManager $jwt;
    private RateLimiter $rateLimiter;
    private EmailService $emailService;
    private CsrfProtection $csrf;

    public function __construct(
        PDO $db,
        AuthConfig $config,
        ?SessionManager $session = null,
        ?JwtManager $jwt = null,
        ?RateLimiter $rateLimiter = null,
        ?EmailService $emailService = null,
        ?CsrfProtection $csrf = null
    ) {
        $this->db = $db;
        $this->config = $config;
        $this->session = $session ?? new SessionManager($config);
        $this->jwt = $jwt ?? new JwtManager($config);
        $this->rateLimiter = $rateLimiter ?? new RateLimiter($db, $config);
        $this->emailService = $emailService ?? new EmailService($config);
        $this->csrf = $csrf ?? new CsrfProtection($this->session);
        
        $this->initializeDatabase();
    }

    /**
     * Register a new user with email verification
     * 
     * @param array $userData User registration data
     * @param bool $requireEmailVerification Whether to require email verification
     * @return AuthResult Registration result with user data or error
     */
    public function register(array $userData, bool $requireEmailVerification = true): AuthResult
    {
        try {
            // Validate input data
            $validatedData = $this->validateRegistrationData($userData);
            
            // Check if user already exists
            if ($this->userExists($validatedData['email'])) {
                return new AuthResult(false, 'User already exists', null, ['email' => 'Email already registered']);
            }

            // Hash password securely
            $hashedPassword = $this->hashPassword($validatedData['password']);
            
            // Generate verification token if required
            $verificationToken = $requireEmailVerification ? $this->generateSecureToken() : null;
            
            // Create user record
            $userId = $this->createUser([
                'email' => $validatedData['email'],
                'password' => $hashedPassword,
                'name' => $validatedData['name'] ?? null,
                'email_verification_token' => $verificationToken,
                'email_verified_at' => $requireEmailVerification ? null : new DateTime(),
                'created_at' => new DateTime(),
                'updated_at' => new DateTime()
            ]);

            $user = $this->getUserById($userId);
            
            // Send verification email if required
            if ($requireEmailVerification && $verificationToken) {
                $this->emailService->sendVerificationEmail($user, $verificationToken);
            }

            return new AuthResult(true, 'Registration successful', $user);

        } catch (ValidationException $e) {
            return new AuthResult(false, 'Validation failed', null, $e->getErrors());
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return new AuthResult(false, 'Registration failed', null);
        }
    }

    /**
     * Authenticate user with email and password
     * 
     * @param string $email User's email address
     * @param string $password User's password
     * @param string $ipAddress Client's IP address for rate limiting
     * @param bool $remember Whether to create a remember token
     * @return AuthResult Authentication result
     */
    public function login(string $email, string $password, string $ipAddress, bool $remember = false): AuthResult
    {
        try {
            // Validate input
            $email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
            if (!$email) {
                return new AuthResult(false, 'Invalid email format');
            }

            // Check rate limiting
            if (!$this->rateLimiter->attemptLogin($email, $ipAddress)) {
                return new AuthResult(false, 'Too many login attempts. Please try again later.');
            }

            // Get user from database
            $user = $this->getUserByEmail($email);
            if (!$user) {
                $this->rateLimiter->recordFailedAttempt($email, $ipAddress);
                return new AuthResult(false, 'Invalid credentials');
            }

            // Check if account is locked
            if ($user->isLocked()) {
                return new AuthResult(false, 'Account is locked. Please contact support.');
            }

            // Verify password
            if (!$this->verifyPassword($password, $user->getPassword())) {
                $this->rateLimiter->recordFailedAttempt($email, $ipAddress);
                $this->incrementFailedAttempts($user);
                return new AuthResult(false, 'Invalid credentials');
            }

            // Check email verification
            if (!$user->isEmailVerified() && $this->config->requireEmailVerification) {
                return new AuthResult(false, 'Please verify your email address before logging in');
            }

            // Check if 2FA is required
            if ($user->has2FAEnabled()) {
                // Store pending 2FA session
                $this->session->set('pending_2fa_user_id', $user->getId());
                return new AuthResult(false, 'Two-factor authentication required', null, [], '2fa_required');
            }

            // Successful login
            $this->rateLimiter->clearFailedAttempts($email);
            $this->clearFailedAttempts($user);
            $this->updateLastLogin($user);

            // Create session or JWT token
            if ($this->config->authMethod === 'session') {
                $this->createUserSession($user, $remember);
            }

            $token = $this->config->authMethod === 'jwt' ? $this->jwt->createToken($user) : null;
            
            $this->currentUser = $user;

            return new AuthResult(true, 'Login successful', $user, [], null, $token);

        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return new AuthResult(false, 'Login failed');
        }
    }

    /**
     * Verify two-factor authentication code
     * 
     * @param string $code 6-digit TOTP code
     * @param string|null $backupCode Optional backup recovery code
     * @return AuthResult Verification result
     */
    public function verify2FA(string $code, ?string $backupCode = null): AuthResult
    {
        try {
            $pendingUserId = $this->session->get('pending_2fa_user_id');
            if (!$pendingUserId) {
                return new AuthResult(false, 'No pending 2FA verification');
            }

            $user = $this->getUserById($pendingUserId);
            if (!$user || !$user->has2FAEnabled()) {
                return new AuthResult(false, 'Invalid 2FA verification');
            }

            $isValid = false;

            // Verify TOTP code
            if ($code && strlen($code) === 6 && ctype_digit($code)) {
                $isValid = $this->verifyTOTPCode($user->get2FASecret(), $code);
            }

            // Verify backup code if TOTP failed
            if (!$isValid && $backupCode) {
                $isValid = $this->verifyBackupCode($user, $backupCode);
            }

            if (!$isValid) {
                return new AuthResult(false, 'Invalid authentication code');
            }

            // Complete login process
            $this->session->remove('pending_2fa_user_id');
            $this->updateLastLogin($user);

            if ($this->config->authMethod === 'session') {
                $this->createUserSession($user);
            }

            $token = $this->config->authMethod === 'jwt' ? $this->jwt->createToken($user) : null;
            $this->currentUser = $user;

            return new AuthResult(true, '2FA verification successful', $user, [], null, $token);

        } catch (Exception $e) {
            error_log("2FA verification error: " . $e->getMessage());
            return new AuthResult(false, '2FA verification failed');
        }
    }

    /**
     * Log out current user
     * 
     * @param bool $allDevices Whether to log out from all devices
     * @return bool Success status
     */
    public function logout(bool $allDevices = false): bool
    {
        try {
            if ($this->currentUser) {
                if ($allDevices) {
                    $this->invalidateAllUserSessions($this->currentUser->getId());
                } else {
                    $this->session->destroy();
                }
                
                $this->currentUser = null;
            }

            return true;
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Initiate password reset process
     * 
     * @param string $email User's email address
     * @return bool Success status
     */
    public function requestPasswordReset(string $email): bool
    {
        try {
            $email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
            if (!$email) {
                return false;
            }

            $user = $this->getUserByEmail($email);
            if (!$user) {
                // Return true to prevent email enumeration
                return true;
            }

            // Generate secure reset token
            $resetToken = $this->generateSecureToken();
            $expiresAt = new DateTime('+1 hour');

            // Store reset token
            $stmt = $this->db->prepare("
                INSERT INTO password_resets (email, token, created_at, expires_at) 
                VALUES (?, ?, NOW(), ?)
                ON DUPLICATE KEY UPDATE token = ?, created_at = NOW(), expires_at = ?
            ");
            
            $stmt->execute([$email, password_hash($resetToken, PASSWORD_ARGON2ID), 
                          $expiresAt->format('Y-m-d H:i:s'), 
                          password_hash($resetToken, PASSWORD_ARGON2ID), 
                          $expiresAt->format('Y-m-d H:i:s')]);

            // Send reset email
            $this->emailService->sendPasswordResetEmail($user, $resetToken);

            return true;
        } catch (Exception $e) {
            error_log("Password reset request error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reset password using reset token
     * 
     * @param string $token Password reset token
     * @param string $newPassword New password
     * @return AuthResult Reset result
     */
    public function resetPassword(string $token, string $newPassword): AuthResult
    {
        try {
            // Validate new password
            if (!$this->isValidPassword($newPassword)) {
                return new AuthResult(false, 'Password does not meet requirements');
            }

            // Find and verify reset token
            $stmt = $this->db->prepare("
                SELECT email, token, expires_at 
                FROM password_resets 
                WHERE expires_at > NOW()
            ");
            $stmt->execute();
            $resetRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $validEmail = null;
            foreach ($resetRecords as $record) {
                if (password_verify($token, $record['token'])) {
                    $validEmail = $record['email'];
                    break;
                }
            }

            if (!$validEmail) {
                return new AuthResult(false, 'Invalid or expired reset token');
            }

            // Get user and update password
            $user = $this->getUserByEmail($validEmail);
            if (!$user) {
                return new AuthResult(false, 'User not found');
            }

            $hashedPassword = $this->hashPassword($newPassword);
            
            $stmt = $this->db->prepare("
                UPDATE users 
                SET password = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$hashedPassword, $user->getId()]);

            // Delete used reset token
            $stmt = $this->db->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->execute([$validEmail]);

            // Invalidate all sessions for security
            $this->invalidateAllUserSessions($user->getId());

            return new AuthResult(true, 'Password reset successful');

        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            return new AuthResult(false, 'Password reset failed');
        }
    }

    /**
     * Enable two-factor authentication for user
     * 
     * @param int $userId User ID
     * @return array Contains QR code URL and backup codes
     */
    public function enable2FA(int $userId): array
    {
        $user = $this->getUserById($userId);
        if (!$user) {
            throw new InvalidArgumentException('User not found');
        }

        // Generate secret key
        $secret = $this->generate2FASecret();
        $backupCodes = $this->generateBackupCodes();

        // Store in database
        $stmt = $this->db->prepare("
            UPDATE users 
            SET two_factor_secret = ?, backup_codes = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$secret, json_encode($backupCodes), $userId]);

        // Generate QR code URL
        $appName = $this->config->appName ?? 'MyApp';
        $qrUrl = $this->generateQRCodeUrl($user->getEmail(), $secret, $appName);

        return [
            'secret' => $secret,
            'qr_code_url' => $qrUrl,
            'backup_codes' => $backupCodes
        ];
    }

    /**
     * Verify email address using verification token
     * 
     * @param string $token Email verification token
     * @return bool Success status
     */
    public function verifyEmail(string $token): bool
    {
        try {
            $stmt = $this->db->prepare("
                SELECT id FROM users 
                WHERE email_verification_token = ? AND email_verified_at IS NULL
            ");
            $stmt->execute([$token]);
            $userId = $stmt->fetchColumn();

            if (!$userId) {
                return false;
            }

            $stmt = $this->db->prepare("
                UPDATE users 
                SET email_verified_at = NOW(), email_verification_token = NULL, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$userId]);

            return true;
        } catch (Exception $e) {
            error_log("Email verification error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get currently authenticated user
     * 
     * @return UserInterface|null Current user or null if not authenticated
     */
    public function getCurrentUser(): ?UserInterface
    {
        if ($this->currentUser) {
            return $this->currentUser;
        }

        // Try to authenticate from session or JWT
        if ($this->config->authMethod === 'session') {
            $userId = $this->session->get('user_id');
            if ($userId) {
                $this->currentUser = $this->getUserById($userId);
            }
        } elseif ($this->config->authMethod === 'jwt') {
            $token = $this->getAuthTokenFromRequest();
            if ($token) {
                $payload = $this->jwt->validateToken($token);
                if ($payload && isset($payload['user_id'])) {
                    $this->currentUser = $this->getUserById($payload['user_id']);
                }
            }
        }

        return $this->currentUser;
    }

    /**
     * Check if user has specific permission
     * 
     * @param string $permission Permission name
     * @param UserInterface|null $user User to check (current user if null)
     * @return bool Has permission
     */
    public function hasPermission(string $permission, ?UserInterface $user = null): bool
    {
        $user = $user ?? $this->getCurrentUser();
        if (!$user) {
            return false;
        }

        return $user->hasPermission($permission);
    }

    /**
     * Check if user has specific role
     * 
     * @param string $role Role name
     * @param UserInterface|null $user User to check (current user if null)
     * @return bool Has role
     */
    public function hasRole(string $role, ?UserInterface $user = null): bool
    {
        $user = $user ?? $this->getCurrentUser();
        if (!$user) {
            return false;
        }

        return $user->hasRole($role);
    }

    /**
     * Generate CSRF token
     * 
     * @return string CSRF token
     */
    public function generateCSRFToken(): string
    {
        return $this->csrf->generateToken();
    }

    /**
     * Verify CSRF token
     * 
     * @param string $token Token to verify
     * @return bool Valid token
     */
    public function verifyCSRFToken(string $token): bool
    {
        return $this->csrf->verifyToken($token);
    }

    /**
     * Middleware for route protection
     * 
     * @param callable $next Next middleware
     * @param array $options Middleware options (roles, permissions)
     * @return mixed Middleware result
     */
    public function middleware(callable $next, array $options = []): mixed
    {
        $user = $this->getCurrentUser();

        // Check authentication
        if (!$user) {
            http_response_code(401);
            return ['error' => 'Authentication required'];
        }

        // Check email verification
        if (!$user->isEmailVerified() && ($options['require_verified'] ?? false)) {
            http_response_code(403);
            return ['error' => 'Email verification required'];
        }

        // Check roles
        if (!empty($options['roles'])) {
            $hasRole = false;
            foreach ($options['roles'] as $role) {
                if ($user->hasRole($role)) {
                    $hasRole = true;
                    break;
                }
            }
            if (!$hasRole) {
                http_response_code(403);
                return ['error' => 'Insufficient permissions'];
            }
        }

        // Check permissions
        if (!empty($options['permissions'])) {
            foreach ($options['permissions'] as $permission) {
                if (!$user->hasPermission($permission)) {
                    http_response_code(403);
                    return ['error' => 'Insufficient permissions'];
                }
            }
        }

        return $next();
    }

    // Private helper methods

    private function initializeDatabase(): void
    {
        $tables = [
            "CREATE TABLE IF NOT EXISTS users (
                id INT PRIMARY KEY AUTO_INCREMENT,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                name VARCHAR(255),
                email_verified_at DATETIME NULL,
                email_verification_token VARCHAR(255) NULL,
                two_factor_secret VARCHAR(255) NULL,
                backup_codes JSON NULL,
                failed_attempts INT DEFAULT 0,
                locked_until DATETIME NULL,
                last_login_at DATETIME NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                INDEX idx_email (email),
                INDEX idx_verification_token (email_verification_token)
            )",
            "CREATE TABLE IF NOT EXISTS password_resets (
                email VARCHAR(255) PRIMARY KEY,
                token VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL,
                expires_at DATETIME NOT NULL,
                INDEX idx_expires (expires_at)
            )",
            "CREATE TABLE IF NOT EXISTS login_attempts (
                id INT PRIMARY KEY AUTO_INCREMENT,
                email VARCHAR(255) NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                attempted_at DATETIME NOT NULL,
                INDEX idx_email_ip (email, ip_address),
                INDEX idx_attempted_at (attempted_at)
            )",
            "CREATE TABLE IF NOT EXISTS user_sessions (
                id VARCHAR(255) PRIMARY KEY,
                user_id INT NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                last_activity DATETIME NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
            "CREATE TABLE IF NOT EXISTS roles (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(255) UNIQUE NOT NULL,
                description TEXT
            )",
            "CREATE TABLE IF NOT EXISTS permissions (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(255) UNIQUE NOT NULL,
                description TEXT
            )",
            "CREATE TABLE IF NOT EXISTS role_permissions (
                role_id INT,
                permission_id INT,
                PRIMARY KEY (role_id, permission_id),
                FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
                FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
            )",
            "CREATE TABLE IF NOT EXISTS user_roles (
                user_id INT,
                role_id INT,
                PRIMARY KEY (user_id, role_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
            )"
        ];

        foreach ($tables as $sql) {
            $this->db->exec($sql);
        }
    }

    private function validateRegistrationData(array $data): array
    {
        $validator = new InputValidator();
        
        $rules = [
            'email' => 'required|email|max:255',
            'password' => 'required|min:8|password',
            'name' => 'nullable|string|max:255'
        ];

        return $validator->validate($data, $rules);
    }

    private function userExists(string $email): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return (bool)$stmt->fetchColumn();
    }

    private function createUser(array $userData): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (email, password, name, email_verification_token, email_verified_at, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userData['email'],
            $userData['password'],
            $userData['name'],
            $userData['email_verification_token'],
            $userData['email_verified_at']?->format('Y-m-d H:i:s'),
            $userData['created_at']->format('Y-m-d H:i:s'),
            $userData['updated_at']->format('Y-m-d H:i:s')
        ]);

        return (int)$this->db->lastInsertId();
    }

    private function getUserById(int $id): ?UserInterface
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        return $userData ? new User($userData, $this->db) : null;
    }

    private function getUserByEmail(string $email): ?UserInterface
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        return $userData ? new User($userData, $this->db) : null;
    }

    private function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }

    private function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    private function isValidPassword(string $password): bool
    {
        return strlen($password) >= 8 && 
               preg_match('/[A-Z]/', $password) &&
               preg_match('/[a-z]/', $password) &&
               preg_match('/\d/', $password) &&
               preg_match('/[^A-Za-z0-9]/', $password);
    }

    private function generateSecureToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function generate2FASecret(): string
    {
        return base32_encode(random_bytes(20));
    }

    private function generateBackupCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $codes[] = sprintf('%04d-%04d', mt_rand(0, 9999), mt_rand(0, 9999));
        }
        return $codes;
    }

    private function generateQRCodeUrl(string $email, string $secret, string $appName): string
    {
        $otpUrl = sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s',
            urlencode($appName),
            urlencode($email),
            $secret,
            urlencode($appName)
        );
        
        return 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($otpUrl);
    }

    private function verifyTOTPCode(string $secret, string $code): bool
    {
        // Implementation would use a TOTP library like RobThree/TwoFactorAuth
        // This is a simplified version
        $timeSlice = floor(time() / 30);
        
        for ($i = -1; $i <= 1; $i++) {
            $calculatedCode = $this->calculateTOTPCode($secret, $timeSlice + $i);
            if (hash_equals($code, $calculatedCode)) {
                return true;
            }
        }
        
        return false;
    }

    private function calculateTOTPCode(string $secret, int $timeSlice): string
    {
        $key = base32_decode($secret);
        $time = pack('N*', 0, $timeSlice);
        $hash = hash_hmac('sha1', $time, $key, true);
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;
        
        return sprintf('%06d', $code);
    }

    private function verifyBackupCode(UserInterface $user, string $code): bool
    {
        $backupCodes = json_decode($user->getBackupCodes() ?? '[]', true);
        
        foreach ($backupCodes as $index => $backupCode) {
            if (hash_equals($code, $backupCode)) {
                // Remove used backup code
                unset($backupCodes[$index]);
                $stmt = $this->db->prepare("UPDATE users SET backup_codes = ? WHERE id = ?");
                $stmt->execute([json_encode(array_values($backupCodes)), $user->getId()]);
                return true;
            }
        }
        
        return false;
    }

    private function createUserSession(UserInterface $user, bool $remember = false): void
    {
        $this->session->regenerate();
        $this->session->set('user_id', $user->getId());
        $this->session->set('user_email', $user->getEmail());
        
        if ($remember) {
            $this->session->extendLifetime(2592000); // 30 days
        }
    }

    private function invalidateAllUserSessions(int $userId): void
    {
        $stmt = $this->db->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        $stmt->execute([$userId]);
    }

    private function incrementFailedAttempts(UserInterface $user): void
    {
        $attempts = $user->getFailedAttempts() + 1;
        $lockUntil = null;
        
        if ($attempts >= $this->config->maxLoginAttempts) {
            $lockUntil = new DateTime('+' . $this->config->lockoutDuration . ' minutes');
        }

        $stmt = $this->db->prepare("
            UPDATE users 
            SET failed_attempts = ?, locked_until = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$attempts, $lockUntil?->format('Y-m-d H:i:s'), $user->getId()]);
    }

    private function clearFailedAttempts(UserInterface $user): void
    {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET failed_attempts = 0, locked_until = NULL, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$user->getId()]);
    }

    private function updateLastLogin(UserInterface $user): void
    {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET last_login_at = NOW(), updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$user->getId()]);
    }

    private function getAuthTokenFromRequest(): ?string
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? null;
        
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }
        
        return null;
    }
}