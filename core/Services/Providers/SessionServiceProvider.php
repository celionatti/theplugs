<?php

declare(strict_types=1);

namespace Plugs\Services\Providers;

use Plugs\Plugs;
use Plugs\Container\Container;
use Plugs\Session\SessionManager;
use Plugs\Services\ServiceProvider;
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
    }

    /**
     * Register the session manager implementation.
     */
    protected function registerSessionManager(): void
    {
        $this->app->singleton('session', function (Container $app) {
            $plugs = $app->get(Plugs::class);
            $config = $this->loadSessionConfig($plugs);
            
            return new SessionManager($config);
        });

        // Alias for convenience
        $this->app->alias('session', SessionManager::class);
    }

    /**
     * Register the session middleware.
     */
    protected function registerSessionMiddleware(): void
    {
        $this->app->singleton(StartSessionMiddleware::class, function (Container $app) {
            return new StartSessionMiddleware($app->get('session'));
        });
    }

    /**
     * Load session configuration from file.
     */
    protected function loadSessionConfig(Plugs $plugs): array
    {
        $configFile = $plugs->basePath('config/session.php');
        
        if (file_exists($configFile)) {
            $config = require $configFile;
            return is_array($config) ? $config : [];
        }
        
        return $this->getDefaultConfig();
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
                'path' => $this->app->get(Plugs::class)->storagePath('framework/sessions'),
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

    /**
     * Bootstrap session services.
     */
    public function boot(): void
    {
        $this->configureSession();
        $this->registerSessionEncryptor();
    }

    /**
     * Configure session settings.
     */
    protected function configureSession(): void
    {
        if ($this->app->has('session')) {
            $session = $this->app->get('session');
            $plugs = $this->app->get(Plugs::class);
            $config = $this->loadSessionConfig($plugs);
            
            // Start session automatically if configured to do so
            if ($config['auto_start'] ?? false) {
                $session->start();
            }
        }
    }

    /**
     * Register the session encryptor if encryption is enabled.
     */
    protected function registerSessionEncryptor(): void
    {
        $this->app->bind(SessionEncryptor::class, function (Container $app) {
            $plugs = $app->get(Plugs::class);
            $config = $this->loadSessionConfig($plugs);
            
            return new SessionEncryptor(
                $config['key'] ?? 'your-secret-key-here',
                $config['cipher'] ?? 'AES-256-CBC'
            );
        });
    }
}