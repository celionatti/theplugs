<?php

declare(strict_types=1);

namespace Plugs\Services\Providers;

use Plugs\Services\ServiceProvider;
use Plugs\Session\SessionManager;
use Plugs\Session\SessionEncryptor;
use Plugs\Session\SessionHelper;
use Plugs\Session\Middleware\StartSessionMiddleware;

class SessionServiceProvider extends ServiceProvider
{
    /**
     * Register session services.
     */
    public function register(): void
    {
        $this->registerSessionManager();
        $this->registerSessionHelper();
        $this->registerSessionMiddleware();
        $this->registerSessionEncryptor();
    }

    /**
     * Register the session manager implementation.
     */
    protected function registerSessionManager(): void
    {
        $this->singleton('session', function () {
            $config = $this->getSessionConfig();
            return new SessionManager($config);
        });

        // Alias for convenience
        $this->alias('session', SessionManager::class);
    }

    /**
     * Register the session helper for easy access.
     */
    protected function registerSessionHelper(): void
    {
        $this->singleton(SessionHelper::class, function () {
            return new SessionHelper($this->container->get('session'));
        });
    }

    /**
     * Register the session middleware.
     */
    protected function registerSessionMiddleware(): void
    {
        $this->singleton(StartSessionMiddleware::class, function () {
            return new StartSessionMiddleware($this->container->get('session'));
        });
    }

    /**
     * Register the session encryptor (optional, only if encryption is enabled).
     */
    protected function registerSessionEncryptor(): void
    {
        $this->bind(SessionEncryptor::class, function () {
            $config = $this->getSessionConfig();

            if (!($config['encrypt'] ?? false)) {
                throw new \RuntimeException('Session encryption is not enabled in configuration.');
            }

            $key = $config['key'] ?? null;
            if (empty($key)) {
                throw new \RuntimeException('Session encryption key is not set in configuration.');
            }

            return new SessionEncryptor(
                $key,
                $config['cipher'] ?? 'AES-256-CBC'
            );
        });
    }

    /**
     * Bootstrap session services.
     */
    public function boot(): void
    {
        $this->configureSession();
        $this->registerGlobalHelpers();
    }

    /**
     * Configure session settings.
     */
    protected function configureSession(): void
    {
        if ($this->container->has('session')) {
            $session = $this->container->get('session');
            $config = $this->getSessionConfig();

            // Start session automatically if configured to do so
            if ($config['auto_start'] ?? false) {
                $session->start();
            }
        }
    }

    /**
     * Register global helper functions (optional).
     */
    protected function registerGlobalHelpers(): void
    {
        if (!function_exists('session')) {
            /**
             * Get the session manager instance or a specific value.
             */
            function session(?string $key = null, mixed $default = null): mixed
            {
                $session = app('session');

                if ($key === null) {
                    return $session;
                }

                return $session->get($key, $default);
            }
        }

        if (!function_exists('csrf_token')) {
            /**
             * Get the CSRF token value.
             */
            function csrf_token(): string
            {
                return app('session')->token();
            }
        }

        if (!function_exists('csrf_field')) {
            /**
             * Generate a CSRF token field for forms.
             */
            function csrf_field(): string
            {
                $token = csrf_token();
                return '<input type="hidden" name="_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
            }
        }

        if (!function_exists('old')) {
            /**
             * Retrieve old input value.
             */
            function old(string $key, mixed $default = null): mixed
            {
                $oldInput = session('_old_input', []);
                return $oldInput[$key] ?? $default;
            }
        }
    }

    /**
     * Get session configuration.
     */
    protected function getSessionConfig(): array
    {
        // Try to get from new config system first
        $config = $this->config('session', []);

        // If empty, try to load from file directly
        if (empty($config)) {
            $config = $this->loadConfig('session.php', $this->getDefaultConfig());
        }

        // Ensure required keys exist
        $config = array_merge($this->getDefaultConfig(), $config);

        // Set save_path if specified and directory exists
        if (isset($config['save_path'])) {
            $savePath = $config['save_path'];

            // Create directory if it doesn't exist
            if (!is_dir($savePath)) {
                @mkdir($savePath, 0755, true);
            }

            // Only set if directory is writable
            if (!is_dir($savePath) || !is_writable($savePath)) {
                unset($config['save_path']);
            }
        }

        return $config;
    }

    /**
     * Get default session configuration.
     */
    protected function getDefaultConfig(): array
    {
        return [
            // Session cookie name
            'cookie' => 'plugs_session',

            // Session lifetime in minutes
            'lifetime' => 120,

            // Expire session when browser closes
            'expire_on_close' => false,

            // Encrypt session data (requires 'key' to be set)
            'encrypt' => false,

            // Encryption key (only needed if encrypt is true)
            'key' => '', // Should be overridden in config

            // Cookie settings
            'path' => '/',
            'domain' => null,
            'secure' => false, // Set true for HTTPS only
            'http_only' => true,
            'same_site' => 'lax', // 'lax', 'strict', or 'none'

            // Garbage collection settings
            'gc_probability' => 1,
            'gc_divisor' => 100,

            // Security options
            'check_ip' => false,
            'check_user_agent' => false,

            // Auto-start session on boot
            'auto_start' => true,

            // Optional: Custom save path (leave null to use PHP default)
            // 'save_path' => $this->app->storagePath('framework/sessions'),
            'save_path' => null,

            // Encryption cipher (only used if encrypt is true)
            'cipher' => 'AES-256-CBC',
        ];
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            'session',
            SessionManager::class,
            SessionHelper::class,
            SessionEncryptor::class,
            StartSessionMiddleware::class,
        ];
    }
}
