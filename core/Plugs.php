<?php

declare(strict_types=1);

namespace Plugs;

use Throwable;
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

class Plugs
{
    /**
     * The singleton instance of the application.
     */
    private static ?Plugs $instance = null;

    private Container $container;

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
        $baseUrlPath = rtrim($baseUrlPath, '/'); // Remove trailing slash

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
     * Get the path to the application routes files.
     */
    public function storagePath(string $path = ''): string
    {
        return $this->basePath('routes') . ($path ? DIRECTORY_SEPARATOR . $path : '');
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
        $this->registerServiceProviders();
        $this->bootServiceProviders();
        $this->loadRoutes();

        $this->hasBeenBootstrapped = true;
    }

    private function loadFunctions(): void
    {
        $functionsDir = __DIR__ . '/functions/';

        // Check if directory exists
        if (!is_dir($functionsDir)) {
            return;
        }

        // Get all PHP files in the directory
        $files = glob($functionsDir . '*.php');

        // Load each file
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
        if (isset($this->loadedProviders[$providerName])) {
            if (!$force) {
                return $this->loadedProviders[$providerName];
            }
            $this->unregister($providerName);
        }

        $this->serviceProviders[] = $provider;
        $this->loadedProviders[$providerName] = $provider;

        // Immediately register if not deferred
        $provider->register();

        return $provider;
    }

    /**
     * Unregister a service provider
     */
    public function unregister(string $providerName): void
    {
        if (isset($this->loadedProviders[$providerName])) {
            unset($this->loadedProviders[$providerName]);
            $this->serviceProviders = array_filter(
                $this->serviceProviders,
                fn($p) => get_class($p) !== $providerName
            );
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

    public function isProviderRegistered(string $provider): bool
    {
        return isset($this->loadedProviders[$provider]);
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

        // Initialize router directly without container
        $this->initializeRouter();

        // These would be core framework providers
        $this->register(new ViewServiceProvider($this->container));
        $this->register(new SessionServiceProvider($this->container));
        // $this->register(new RoutingServiceProvider($this));
        // $this->register(new ExceptionServiceProvider($this));
    }

    /**
     * Register container aliases.
     */
    protected function registerCoreContainerAliases(): void
    {
        foreach (
            [
                'app' => [self::class, Container::class],
                'view' => [View::class, 'Plugs\View\View'],
                // 'router' => [Router::class, 'Plugs\Http\Router\Router'],
            ] as $key => $aliases
        ) {
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
        $this->container->instance('path.config', $this->configPath());
        $this->container->instance('path.routes', $this->routesPath());
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
     * Load configuration files.
     */
    protected function loadConfiguration(): void
    {
        $configPath = $this->configPath('app.php');

        if (file_exists($configPath)) {
            $config = require $configPath;
            $this->container->instance('config', $config);
        }
    }

    /**
     * Register all service providers from configuration.
     */
    protected function registerServiceProviders(): void
    {
        $providersFile = $this->configPath('providers.php');

        if (file_exists($providersFile)) {
            $providers = require $providersFile;

            foreach ($providers as $provider) {
                $this->register($provider);
            }
        }
    }

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

        // Make it available in the app if needed
        $this->container->instance(Router::class, $router);
    }

    /**
     * Load application routes.
     */
    protected function loadRoutes(): void
    {
        $webRoutesFile = $this->routesPath('web.php');

        if (file_exists($webRoutesFile)) {
            require $webRoutesFile;
        }
    }

    public function shouldSkipMiddleware(Request $request): bool
    {
        // Logic to determine if middleware should be skipped
        // For example, check for specific routes or request types
        return false;
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
}
