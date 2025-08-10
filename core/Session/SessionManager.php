<?php

declare(strict_types=1);

namespace Plugs\Session;

use Plugs\Session\Driver\FileSessionDriver;
use Plugs\Session\Driver\ArraySessionDriver;
use Plugs\Session\Interface\SessionInterface;
use Plugs\Session\Driver\DatabaseSessionDriver;
use Plugs\Session\Interface\SessionDriverInterface;

class SessionManager implements SessionInterface
{
    private SessionDriverInterface $driver;
    private array $config;
    private array $data = [];
    private ?string $sessionId = null;
    private bool $started = false;
    private ?SessionEncryptor $encryptor = null;
    private array $flashKeys = [];

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->driver = $this->createDriver();
        
        if ($config['encrypt'] ?? false) {
            $this->encryptor = new SessionEncryptor($config['key']);
        }
    }

    /**
     * Create session driver based on config
     */
    private function createDriver(): SessionDriverInterface
    {
        $driver = $this->config['driver'] ?? 'file';
        
        return match ($driver) {
            'file' => new FileSessionDriver($this->config),
            'database' => new DatabaseSessionDriver($this->config),
            'array' => new ArraySessionDriver(),
            default => throw new \InvalidArgumentException("Unsupported session driver: {$driver}")
        };
    }

    /**
     * Start the session
     */
    public function start(): bool
    {
        if ($this->started) {
            return true;
        }

        $this->configureSession();
        
        if (session_status() === PHP_SESSION_NONE) {
            if (!session_start()) {
                return false;
            }
        }

        $this->sessionId = session_id();
        $this->loadSessionData();
        $this->handleFlashData();
        $this->started = true;

        return true;
    }

    /**
     * Configure PHP session settings
     */
    private function configureSession(): void
    {
        ini_set('session.name', $this->config['cookie']);
        ini_set('session.cookie_lifetime', $this->config['expire_on_close'] ? 0 : $this->config['lifetime'] * 60);
        ini_set('session.cookie_path', $this->config['path'] ?? '/');
        ini_set('session.cookie_domain', $this->config['domain'] ?? '');
        ini_set('session.cookie_secure', $this->config['secure'] ? '1' : '0');
        ini_set('session.cookie_httponly', $this->config['http_only'] ? '1' : '0');
        ini_set('session.cookie_samesite', $this->config['same_site'] ?? 'Lax');
        ini_set('session.gc_maxlifetime', $this->config['lifetime'] * 60);
        
        // Set custom session handler
        session_set_save_handler(
            [$this, 'open'],
            [$this, 'close'],
            [$this, 'read'],
            [$this, 'write'],
            [$this, 'destroy'],
            [$this, 'gc']
        );
    }

    /**
     * Load session data from storage
     */
    private function loadSessionData(): void
    {
        $data = $this->driver->read($this->sessionId);
        
        if ($data && $this->encryptor) {
            $data = $this->encryptor->decrypt($data);
        }
        
        $this->data = $data ? unserialize($data) : [];
        
        // Security checks
        if ($this->config['check_ip'] ?? false) {
            $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
            if (isset($this->data['_ip']) && $this->data['_ip'] !== $currentIp) {
                $this->invalidate();
                return;
            }
            $this->data['_ip'] = $currentIp;
        }
        
        if ($this->config['check_user_agent'] ?? false) {
            $currentAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            if (isset($this->data['_user_agent']) && $this->data['_user_agent'] !== $currentAgent) {
                $this->invalidate();
                return;
            }
            $this->data['_user_agent'] = $currentAgent;
        }
    }

    /**
     * Handle flash data expiration
     */
    private function handleFlashData(): void
    {
        $this->flashKeys = $this->data['_flash']['new'] ?? [];
        
        // Remove old flash data
        if (isset($this->data['_flash']['old'])) {
            foreach ($this->data['_flash']['old'] as $key) {
                unset($this->data[$key]);
            }
        }
        
        // Move new flash data to old
        $this->data['_flash'] = [
            'old' => $this->flashKeys,
            'new' => []
        ];
    }

    /**
     * Store a value in the session
     */
    public function put(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Get a value from the session
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Check if a key exists in the session
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Remove one or more values from the session
     */
    public function forget(string|array $keys): void
    {
        $keys = is_array($keys) ? $keys : [$keys];
        
        foreach ($keys as $key) {
            unset($this->data[$key]);
        }
    }

    /**
     * Get all session data
     */
    public function all(): array
    {
        return array_filter($this->data, function($key) {
            return !str_starts_with($key, '_');
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Store flash data for the next request
     */
    public function flash(string $key, mixed $value): void
    {
        $this->put($key, $value);
        $this->data['_flash']['new'][] = $key;
    }

    /**
     * Keep all flash data for another request
     */
    public function reflash(): void
    {
        $this->data['_flash']['new'] = array_merge(
            $this->data['_flash']['new'] ?? [],
            $this->data['_flash']['old'] ?? []
        );
        $this->data['_flash']['old'] = [];
    }

    /**
     * Clear all session data
     */
    public function clear(): void
    {
        $this->data = [];
    }

    /**
     * Destroy the session and regenerate ID
     */
    public function invalidate(): bool
    {
        $this->clear();
        return $this->regenerate();
    }

    /**
     * Regenerate the session ID
     */
    public function regenerate(): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            $this->sessionId = session_id();
        }
        return true;
    }

    /**
     * Get or generate CSRF token
     */
    public function token(): string
    {
        if (!$this->has('_token')) {
            $this->put('_token', bin2hex(random_bytes(32)));
        }
        
        return $this->get('_token');
    }

    /**
     * Get session ID
     */
    public function getId(): ?string
    {
        return $this->sessionId;
    }

    // Session handler methods
    public function open($savePath, $sessionName): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read($sessionId): string
    {
        return $this->driver->read($sessionId);
    }

    public function write($sessionId, $sessionData): bool
    {
        $data = serialize($this->data);
        
        if ($this->encryptor) {
            $data = $this->encryptor->encrypt($data);
        }
        
        return $this->driver->write($sessionId, $data);
    }

    public function destroy($sessionId): bool
    {
        return $this->driver->destroy($sessionId);
    }

    public function gc($maxLifetime): bool
    {
        return $this->driver->gc($maxLifetime);
    }
}