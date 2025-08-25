<?php

declare(strict_types=1);

namespace Plugs;

use Throwable;
use Plugs\Config;
use Plugs\View\View;
use Plugs\Http\Router\Route;
use Plugs\Http\Router\Router;
use Plugs\Container\Container;
use Plugs\Http\Request\Request;
use Plugs\Http\Response\Response;
use Plugs\Services\ServiceProvider;
use Plugs\Exceptions\Handler\ExceptionHandler;
use Plugs\Services\Providers\ViewServiceProvider;
use Plugs\Services\Providers\SessionServiceProvider;
use Plugs\Services\Providers\MiddlewareServiceProvider;

class Plugs
{
    /**
     * The singleton instance of the application.
     */
    private static ?Plugs $instance = null;

    public Container $container;

    /**
     * The base path of the application.
     */
    protected ?string $basePath;
    protected ?string $urlPath = "/";

    /**
     * The environment the application is running in.
     */
    private string $environment = 'production';

    /**
     * Indicates if the application has been bootstrapped.
     */
    private bool $hasBeenBootstrapped = false;

    /**
     * All registered service providers.
     */
    private array $serviceProviders = [];

    /**
     * All loaded service providers.
     */
    private array $loadedProviders = [];

    /**
     * Deferred service providers.
     */
    private array $deferredServices = [];

    /**
     * The HTTP kernel instance.
     */
    private ?Kernel $kernel = null;

    /**
     * The exception handler instance.
     */
    private ?ExceptionHandler $exceptionHandler = null;

    public function __construct(string $basePath)
    {
        $this->container = new Container();
        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();
        $this->registerCoreContainerAliases();
        
        if ($basePath) {
            $this->setBasePath($basePath);
        }

        static::$instance = $this;
    }

    /**
     * Get the singleton instance of the application.
     */
    public static function getInstance(): Plugs
    {
        if (self::$instance === null) {
            self::$instance = new self(dirname(__DIR__));
        }

        return self::$instance;
    }

    /**
     * Set the base path for the application.
     */
    public function setBasePath(string $basePath): self
    {
        $this->basePath = rtrim($basePath, '/');
        $this->bindPathsInContainer();
        return $this;
    }

    /**
     * Get the base path of the application.
     */
    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
    }

    /**
     * Set the URL path for the application.
     */
    public function setUrlPath(string $urlPath): self
    {
        $this->urlPath = rtrim($urlPath, '/') ?: '/';
        return $this;
    }

    /**
     * Get the URL path of the application.
     */
    public function urlPath(string $path = ''): string
    {
        $baseUrlPath = $this->urlPath ?? '/';
        $baseUrlPath = rtrim($baseUrlPath, '/');

        if ($path === '') {
            return $baseUrlPath ?: '/';
        }

        $path = ltrim($path, '/');
        return $baseUrlPath . '/' . $path;
    }

    /**
     * Get the path to the application configuration files.
     */
    public function configPath(string $path = ''): string
    {
        return $this->basePath('config') . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * Get the path to the application routes files.
     */
    public function routesPath(string $path = ''): string
    {
        return $this->basePath('routes') . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * Get the path to the storage directory.
     */
    public function storagePath(string $path = ''): string
    {
        return $this->basePath('storage') . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * Get the path to the application directory.
     */
    public function appPath(string $path = ''): string
    {
        return $this->basePath('app') . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * Get the path to the resources directory.
     */
    public function resourcePath(string $path = ''): string
    {
        return $this->basePath('resources') . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * Get the path to the public directory.
     */
    public function publicPath(string $path = ''): string
    {
        return $this->basePath('public') . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * Bootstrap the application for HTTP requests.
     */
    public function bootstrap(): void
    {
        if ($this->hasBeenBootstrapped) {
            return;
        }

        $this->loadFunctions();
        $this->loadEnvironmentConfiguration();
        $this->loadConfiguration();
        $this->registerConfiguredServiceProviders();
        $this->bootServiceProviders();
        $this->loadRoutes();

        $this->hasBeenBootstrapped = true;
    }

    /**
     * Load helper functions.
     */
    private function loadFunctions(): void
    {
        $functionsDir = __DIR__ . '/functions/';

        if (!is_dir($functionsDir)) {
            return;
        }

        $files = glob($functionsDir . '*.php');

        foreach ($files as $file) {
            if (file_exists($file) && is_file($file)) {
                require_once $file;
            }
        }
    }

    /**
     * Handle an incoming HTTP request and return the response.
     */
    public function handle(Request $request): Response
    {
        try {
            $this->bootstrap();
            return $this->getKernel()->handle($request);
        } catch (Throwable $e) {
            return $this->handleException($request, $e);
        }
    }

    /**
     * Register a service provider.
     */
    public function register(string|ServiceProvider $provider, bool $force = false): ServiceProvider
    {
        if (is_string($provider)) {
            $provider = new $provider($this);
        }

        $providerName = get_class($provider);

        // If already registered and not forcing, return existing instance
        if (isset($this->loadedProviders[$providerName]) && !$force) {
            return $this->loadedProviders[$providerName];
        }

        // If forcing and already registered, unregister first
        if ($force && isset($this->loadedProviders[$providerName])) {
            $this->unregister($providerName);
        }

        $this->serviceProviders[] = $provider;
        $this->loadedProviders[$providerName] = $provider;

        // Handle deferred providers
        if ($provider->isDeferred()) {
            $this->addDeferredServices($provider);
        } else {
            // Immediately register if not deferred
            $provider->register();
        }

        return $provider;
    }

    /**
     * Unregister a service provider.
     */
    public function unregister(string $providerName): void
    {
        if (isset($this->loadedProviders[$providerName])) {
            unset($this->loadedProviders[$providerName]);
            $this->serviceProviders = array_filter(
                $this->serviceProviders,
                fn($p) => get_class($p) !== $providerName
            );
            
            // Remove from deferred services if applicable
            $this->deferredServices = array_filter(
                $this->deferredServices,
                fn($provider) => $provider !== $providerName
            );
        }
    }

    /**
     * Add deferred services to the list.
     */
    protected function addDeferredServices(ServiceProvider $provider): void
    {
        $services = $provider->provides();
        
        foreach ($services as $service) {
            $this->deferredServices[$service] = get_class($provider);
        }
    }

    /**
     * Load a deferred service provider.
     */
    protected function loadDeferredProvider(string $service): void
    {
        if (!isset($this->deferredServices[$service])) {
            return;
        }

        $providerClass = $this->deferredServices[$service];
        
        if (!isset($this->loadedProviders[$providerClass])) {
            $this->register($providerClass);
        }

        unset($this->deferredServices[$service]);
    }

    /**
     * Register multiple service providers at once.
     */
    public function registerProviders(array $providers): void
    {
        foreach ($providers as $provider) {
            $this->register($provider);
        }
    }

    /**
     * Boot all registered service providers.
     */
    protected function bootServiceProviders(): void
    {
        foreach ($this->serviceProviders as $provider) {
            if (method_exists($provider, 'boot')) {
                $provider->boot();
            }
        }
    }

    /**
     * Check if a service provider is registered.
     */
    public function isProviderRegistered(string|object $provider): bool
    {
        $providerName = is_string($provider) ? $provider : get_class($provider);
        return isset($this->loadedProviders[$providerName]);
    }

    /**
     * Get all registered service providers.
     */
    public function getProviders(): array
    {
        return $this->serviceProviders;
    }

    /**
     * Get all loaded service providers.
     */
    public function getLoadedProviders(): array
    {
        return $this->loadedProviders;
    }

    /**
     * Get the current environment.
     */
    public function environment(): string
    {
        return $this->environment;
    }

    /**
     * Determine if the application is in the given environment.
     */
    public function isEnvironment(string ...$environments): bool
    {
        return in_array($this->environment(), $environments);
    }

    /**
     * Detect the environment using a callback.
     */
    public function detectEnvironment(callable $callback): void
    {
        $this->environment = call_user_func($callback) ?: 'production';
    }

    /**
     * Get the HTTP kernel instance.
     */
    protected function getKernel(): Kernel
    {
        if (!$this->kernel) {
            $this->kernel = new Kernel($this->container);
        }

        return $this->kernel;
    }

    /**
     * Handle an exception.
     */
    public function handleException(Request $request, Throwable $exception): Response
    {
        if (!$this->exceptionHandler) {
            $this->exceptionHandler = new ExceptionHandler($this);
        }

        return $this->exceptionHandler->render($request, $exception);
    }

    /**
     * Register the basic bindings into the container.
     */
    protected function registerBaseBindings(): void
    {
        $this->container::setInstance($this->container);
        $this->container->instance('app', $this);
        $this->container->instance(Plugs::class, $this);
        $this->container->instance(Container::class, $this->container);
    }

    /**
     * Register base service providers.
     */
    protected function registerBaseServiceProviders(): void
    {
        // Register the exception handler as a singleton
        $this->container->singleton(ExceptionHandler::class, function ($app) {
            return new ExceptionHandler($app);
        });

        // Initialize router directly
        $this->initializeRouter();

        // Register core framework providers
        $this->register(new ViewServiceProvider($this));
        $this->register(new SessionServiceProvider($this));
        $this->register(new MiddlewareServiceProvider($this));
    }

    /**
     * Register container aliases.
     */
    protected function registerCoreContainerAliases(): void
    {
        foreach ([
            'app' => [self::class, Container::class],
            'view' => [View::class, 'Plugs\View\View'],
            'config' => [Config::class, 'Plugs\Config\Config'],
        ] as $key => $aliases) {
            foreach ($aliases as $alias) {
                $this->container->alias($key, $alias);
            }
        }
    }

    /**
     * Bind all paths in the container.
     */
    protected function bindPathsInContainer(): void
    {
        $this->container->instance('path.base', $this->basePath());
        $this->container->instance('path.app', $this->appPath());
        $this->container->instance('path.config', $this->configPath());
        $this->container->instance('path.routes', $this->routesPath());
        $this->container->instance('path.storage', $this->storagePath());
        $this->container->instance('path.resources', $this->resourcePath());
        $this->container->instance('path.public', $this->publicPath());
        $this->container->instance('path.url', $this->urlPath());
    }

    /**
     * Load environment configuration.
     */
    protected function loadEnvironmentConfiguration(): void
    {
        $envFile = $this->basePath('.env');

        // Default values
        $this->environment = 'production';
        $this->urlPath = '/';

        if (file_exists($envFile)) {
            $this->loadEnvFile($envFile);
            $this->environment = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? $this->environment;
            $this->urlPath = $_ENV['APP_URL_PATH'] ?? $_SERVER['APP_URL_PATH'] ?? $this->urlPath;
        }
    }

    /**
     * Load configuration files using the new Config class.
     */
    protected function loadConfiguration(): void
    {
        try {
            // Initialize the Config class with the config directory
            Config::initialize($this->configPath());

            // Load all configuration files
            Config::loadAll();

            // Apply environment-specific overrides if they exist
            $this->loadEnvironmentSpecificConfiguration();

            // Bind config instance to container
            $this->container->singleton('config', function () {
                return new class {
                    public function get(string $key, mixed $default = null): mixed
                    {
                        return Config::get($key, $default);
                    }

                    public function set(string $key, mixed $value): void
                    {
                        Config::set($key, $value);
                    }

                    public function has(string $key): bool
                    {
                        return Config::has($key);
                    }

                    public function all(): array
                    {
                        return Config::all();
                    }
                };
            });

            // Update environment and URL path from config if not set in .env
            if (!isset($_ENV['APP_ENV'])) {
                $this->environment = Config::get('app.env', $this->environment);
            }

            if (!isset($_ENV['APP_URL_PATH'])) {
                $this->urlPath = Config::get('app.url_path', $this->urlPath);
            }
        } catch (\Exception $e) {
            // Fallback to old configuration loading if Config class fails
            $this->loadLegacyConfiguration();
        }
    }

    /**
     * Load environment-specific configuration overrides.
     */
    protected function loadEnvironmentSpecificConfiguration(): void
    {
        $envConfigFile = $this->configPath("environments/{$this->environment}.php");

        if (file_exists($envConfigFile)) {
            $envConfig = require $envConfigFile;

            if (is_array($envConfig)) {
                foreach ($envConfig as $file => $config) {
                    if (is_array($config)) {
                        Config::merge($file, $config);
                    }
                }
            }
        }
    }

    /**
     * Fallback method for legacy configuration loading.
     */
    protected function loadLegacyConfiguration(): void
    {
        $configPath = $this->configPath('app.php');

        if (file_exists($configPath)) {
            $config = require $configPath;
            $this->container->instance('config', $config);
        }
    }

    /**
     * Register service providers from configuration.
     */
    protected function registerConfiguredServiceProviders(): void
    {
        $providers = $this->getConfiguredProviders();

        foreach ($providers as $provider) {
            if (class_exists($provider)) {
                $this->register($provider);
            } else {
                // Log warning or throw exception for missing provider
                error_log("Service provider not found: {$provider}");
            }
        }
    }

    /**
     * Get service providers from configuration.
     */
    protected function getConfiguredProviders(): array
    {
        $providers = [];

        // Try to get providers from new Config class first
        try {
            $providers = Config::get('app.providers', []);

            // Also check for environment-specific providers
            $envProviders = Config::get("app.providers.{$this->environment}", []);
            if (!empty($envProviders)) {
                $providers = array_merge($providers, $envProviders);
            }

            // If empty, try to load from dedicated providers file
            if (empty($providers)) {
                $providers = $this->loadProvidersFromFile();
            }
        } catch (\Exception $e) {
            // Fallback to legacy providers file
            $providers = $this->loadProvidersFromFile();
        }

        return array_unique($providers);
    }

    /**
     * Load service providers from dedicated providers file.
     */
    protected function loadProvidersFromFile(): array
    {
        $providersFile = $this->configPath('providers.php');
        
        if (file_exists($providersFile)) {
            $providers = require $providersFile;
            return is_array($providers) ? $providers : [];
        }

        return [];
    }

    /**
     * Initialize the router.
     */
    protected function initializeRouter(): void
    {
        // Create router instance
        $router = new Router($this->container);

        // Set base path if configured and not root
        if ($this->urlPath !== '/') {
            $router->setBasePath($this->urlPath);
        }

        // Set it in the Route facade
        Route::setRouter($router);

        // Make it available in the container
        $this->container->instance(Router::class, $router);
        // $this->container->alias('router', Router::class);
    }

    /**
     * Load application routes.
     */
    protected function loadRoutes(): void
    {
        $routeFiles = [
            'web.php',
            'api.php',
        ];

        foreach ($routeFiles as $routeFile) {
            $routePath = $this->routesPath($routeFile);
            if (file_exists($routePath)) {
                require $routePath;
            }
        }

        // Load environment-specific routes if they exist
        $envRoutesFile = $this->routesPath("{$this->environment}.php");
        if (file_exists($envRoutesFile)) {
            require $envRoutesFile;
        }
    }

    /**
     * Check if middleware should be skipped.
     */
    public function shouldSkipMiddleware(Request $request): bool
    {
        // Logic to determine if middleware should be skipped
        // For example, check for specific routes or request types
        return false;
    }

    /**
     * Get a configuration value (helper method).
     */
    public function config(string $key, mixed $default = null): mixed
    {
        try {
            return Config::get($key, $default);
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Resolve a service from the container or load deferred provider.
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        // Check if this is a deferred service
        if (isset($this->deferredServices[$abstract])) {
            $this->loadDeferredProvider($abstract);
        }

        return $this->container->get($abstract, $parameters);
    }

    /**
     * Determine if the given abstract type has been bound.
     */
    public function bound(string $abstract): bool
    {
        return $this->container->has($abstract) || isset($this->deferredServices[$abstract]);
    }

    /**
     * Load environment variables from file.
     */
    private function loadEnvFile(string $file): void
    {
        if (!file_exists($file)) {
            return;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, '#') === 0 || $line === '') {
                continue;
            }

            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));

            // Remove quotes if present
            $value = trim($value, '"\'');

            if ($key !== '' && !array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }

    /**
     * Get service provider instance by class name.
     */
    public function getProvider(string $providerClass): ?ServiceProvider
    {
        return $this->loadedProviders[$providerClass] ?? null;
    }

    /**
     * Check if the application has been bootstrapped.
     */
    public function hasBeenBootstrapped(): bool
    {
        return $this->hasBeenBootstrapped;
    }

    /**
     * Terminate the application.
     */
    public function terminate(Request $request, Response $response): void
    {
        foreach ($this->serviceProviders as $provider) {
            if (method_exists($provider, 'terminate')) {
                $provider->terminate($request, $response);
            }
        }
    }
}