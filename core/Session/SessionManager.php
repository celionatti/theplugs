<?php

declare(strict_types=1);

namespace Plugs\Session;

use Plugs\Session\Interface\SessionInterface;

class SessionManager implements SessionInterface
{
    private array $config;
    private bool $started = false;
    private ?SessionEncryptor $encryptor = null;

    public function __construct(array $config)
    {
        $this->config = $config;

        if ($config['encrypt'] ?? false) {
            $this->encryptor = new SessionEncryptor($config['key']);
        }
    }

    /**
     * Start the session
     */
    public function start(): bool
    {
        if ($this->started) {
            return true;
        }

        // Only configure if session hasn't started yet
        if (session_status() === PHP_SESSION_NONE) {
            $this->configureSession();

            if (!session_start()) {
                return false;
            }
        }

        $this->handleFlashData();
        $this->handleSecurityChecks();
        $this->started = true;

        return true;
    }

    /**
     * Configure PHP session settings
     */
    private function configureSession(): void
    {
        // Session cookie configuration
        ini_set('session.name', $this->config['cookie'] ?? 'plugs_session');
        ini_set('session.cookie_lifetime', $this->config['expire_on_close'] ? '0' : (string)($this->config['lifetime'] * 60));
        ini_set('session.cookie_path', $this->config['path'] ?? '/');
        ini_set('session.cookie_domain', $this->config['domain'] ?? '');
        ini_set('session.cookie_secure', $this->config['secure'] ? '1' : '0');
        ini_set('session.cookie_httponly', $this->config['http_only'] ? '1' : '0');
        ini_set('session.cookie_samesite', $this->config['same_site'] ?? 'Lax');

        // Session lifetime and garbage collection
        ini_set('session.gc_maxlifetime', (string)($this->config['lifetime'] * 60));
        ini_set('session.gc_probability', (string)($this->config['gc_probability'] ?? 1));
        ini_set('session.gc_divisor', (string)($this->config['gc_divisor'] ?? 100));

        // Optional: Set custom save path if specified
        if (isset($this->config['save_path'])) {
            $savePath = $this->config['save_path'];

            // Create directory if it doesn't exist
            if (!is_dir($savePath)) {
                @mkdir($savePath, 0755, true);
            }

            if (is_dir($savePath) && is_writable($savePath)) {
                ini_set('session.save_path', $savePath);
            }
        }
    }

    /**
     * Handle security checks
     */
    private function handleSecurityChecks(): void
    {
        // Check IP address
        if ($this->config['check_ip'] ?? false) {
            $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
            if (isset($_SESSION['_ip']) && $_SESSION['_ip'] !== $currentIp) {
                $this->invalidate();
                return;
            }
            $_SESSION['_ip'] = $currentIp;
        }

        // Check user agent
        if ($this->config['check_user_agent'] ?? false) {
            $currentAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            if (isset($_SESSION['_user_agent']) && $_SESSION['_user_agent'] !== $currentAgent) {
                $this->invalidate();
                return;
            }
            $_SESSION['_user_agent'] = $currentAgent;
        }
    }

    /**
     * Handle flash data expiration
     */
    private function handleFlashData(): void
    {
        $flashKeys = $_SESSION['_flash']['new'] ?? [];

        // Remove old flash data
        if (isset($_SESSION['_flash']['old'])) {
            foreach ($_SESSION['_flash']['old'] as $key) {
                unset($_SESSION[$key]);
            }
        }

        // Move new flash data to old
        $_SESSION['_flash'] = [
            'old' => $flashKeys,
            'new' => []
        ];
    }

    /**
     * Store a value in the session
     */
    public function put(string $key, mixed $value): void
    {
        $this->ensureStarted();
        $_SESSION[$key] = $value;
    }

    /**
     * Get a value from the session
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if a key exists in the session
     */
    public function has(string $key): bool
    {
        $this->ensureStarted();
        return array_key_exists($key, $_SESSION);
    }

    /**
     * Remove one or more values from the session
     */
    public function forget(string|array $keys): void
    {
        $this->ensureStarted();
        $keys = is_array($keys) ? $keys : [$keys];

        foreach ($keys as $key) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Get all session data (excluding internal keys)
     */
    public function all(): array
    {
        $this->ensureStarted();
        return array_filter($_SESSION, function ($key) {
            return !str_starts_with($key, '_');
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Store flash data for the next request
     */
    public function flash(string $key, mixed $value): void
    {
        $this->ensureStarted();
        $_SESSION[$key] = $value;
        $_SESSION['_flash']['new'][] = $key;
    }

    /**
     * Keep all flash data for another request
     */
    public function reflash(): void
    {
        $this->ensureStarted();
        $_SESSION['_flash']['new'] = array_merge(
            $_SESSION['_flash']['new'] ?? [],
            $_SESSION['_flash']['old'] ?? []
        );
        $_SESSION['_flash']['old'] = [];
    }

    /**
     * Keep specific flash data for another request
     */
    public function keep(string|array $keys): void
    {
        $this->ensureStarted();
        $keys = is_array($keys) ? $keys : [$keys];

        foreach ($keys as $key) {
            if (in_array($key, $_SESSION['_flash']['old'] ?? [])) {
                $_SESSION['_flash']['new'][] = $key;
            }
        }
    }

    /**
     * Clear all session data
     */
    public function clear(): void
    {
        $this->ensureStarted();
        $_SESSION = [];
    }

    /**
     * Destroy the session and regenerate ID
     */
    public function invalidate(): bool
    {
        $this->ensureStarted();
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
            session_start();
            session_regenerate_id(true);
        }

        return true;
    }

    /**
     * Regenerate the session ID
     */
    public function regenerate(): bool
    {
        $this->ensureStarted();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        return true;
    }

    /**
     * Get or generate CSRF token
     */
    public function token(): string
    {
        $this->ensureStarted();

        if (!isset($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_token'];
    }

    /**
     * Verify CSRF token
     */
    public function verifyToken(string $token): bool
    {
        return hash_equals($this->token(), $token);
    }

    /**
     * Get session ID
     */
    public function getId(): ?string
    {
        return session_status() === PHP_SESSION_ACTIVE ? session_id() : null;
    }

    /**
     * Set the session ID
     */
    public function setId(string $id): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_id($id);
        }
    }

    /**
     * Get session name
     */
    public function getName(): string
    {
        return session_name();
    }

    /**
     * Check if session is started
     */
    public function isStarted(): bool
    {
        return $this->started && session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Pull a value from session and delete it
     */
    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->forget($key);
        return $value;
    }

    /**
     * Increment a value in the session
     */
    public function increment(string $key, int $amount = 1): int
    {
        $this->ensureStarted();
        $value = (int)($_SESSION[$key] ?? 0);
        $_SESSION[$key] = $value + $amount;
        return $_SESSION[$key];
    }

    /**
     * Decrement a value in the session
     */
    public function decrement(string $key, int $amount = 1): int
    {
        return $this->increment($key, -$amount);
    }

    /**
     * Ensure session is started before operations
     */
    private function ensureStarted(): void
    {
        if (!$this->started) {
            $this->start();
        }
    }

    /**
     * Get previous URL from session
     */
    public function previousUrl(): ?string
    {
        return $this->get('_previous_url');
    }

    /**
     * Set previous URL in session
     */
    public function setPreviousUrl(string $url): void
    {
        $this->put('_previous_url', $url);
    }

    /**
     * Destroy the session completely
     */
    public function destroy(): bool
    {
        $this->ensureStarted();

        $_SESSION = [];

        // Delete session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        $result = session_destroy();
        $this->started = false;

        return $result;
    }
}
