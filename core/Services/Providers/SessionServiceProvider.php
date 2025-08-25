<?php

declare(strict_types=1);

namespace Plugs\Services\Providers;

use Plugs\Services\ServiceProvider;
use Plugs\Session\SessionManager;
use Plugs\Session\SessionEncryptor;
use Plugs\Session\Middleware\StartSessionMiddleware;

class SessionServiceProvider extends ServiceProvider
{
    /**
     * Register session services.
     */
    public function register(): void
    {
        $this->registerSessionManager();
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
     * Register the session middleware.
     */
    protected function registerSessionMiddleware(): void
    {
        $this->singleton(StartSessionMiddleware::class, function () {
            return new StartSessionMiddleware($this->container->get('session'));
        });
    }

    /**
     * Register the session encryptor.
     */
    protected function registerSessionEncryptor(): void
    {
        $this->bind(SessionEncryptor::class, function () {
            $config = $this->getSessionConfig();
            
            return new SessionEncryptor(
                $config['key'] ?? 'your-secret-key-here',
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

        return $config;
    }

    /**
     * Get default session configuration.
     */
    protected function getDefaultConfig(): array
    {
        return [
            'driver' => 'file',
            'cookie' => 'plugs_session',
            'lifetime' => 120, // minutes
            'expire_on_close' => false,
            'encrypt' => false,
            'key' => 'your-secret-key-here', // Should be overridden in config
            'path' => '/',
            'domain' => null,
            'secure' => false,
            'http_only' => true,
            'same_site' => 'Lax',
            'check_ip' => false,
            'check_user_agent' => false,
            'auto_start' => true,
            'file' => [
                'path' => $this->app->storagePath('framework/sessions'),
            ],
            'database' => [
                'host' => 'localhost',
                'database' => 'plugs',
                'username' => 'root',
                'password' => '',
                'table' => 'sessions',
            ],
            'cipher' => 'AES-256-CBC',
        ];
    }
}