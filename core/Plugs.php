<?php

declare(strict_types=1);

namespace Plugs;

use Throwable;
use Plugs\Http\Router\Router;
use Plugs\Container\Container;
use Plugs\Http\Request\Request;
use Plugs\Http\Response\Response;
use Plugs\Services\ServiceProvider;
use Plugs\Exceptions\Handler\ExceptionHandler;

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

        if ($basePath) {
            $this->setBasePath($basePath);
        }
        
        $this->registerBaseServiceProviders();
        $this->registerCoreContainerAliases();

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
     * Bootstrap the application for HTTP requests.
     */
    public function bootstrap(): void
    {
        if ($this->hasBeenBootstrapped) {
            return;
        }

        $this->loadEnvironmentConfiguration();
        $this->loadConfiguration();
        $this->registerServiceProviders();
        $this->bootServiceProviders();
        $this->loadRoutes();

        $this->hasBeenBootstrapped = true;
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
    public function register(string|ServiceProvider $provider, bool $defer = false): ServiceProvider
    {
        if (is_string($provider)) {
            $provider = new $provider($this);
        }

        if (array_key_exists($providerName = get_class($provider), $this->loadedProviders)) {
            return $this->loadedProviders[$providerName];
        }

        $this->serviceProviders[] = $provider;

        if (!$defer) {
            $provider->register();
        }

        $this->loadedProviders[get_class($provider)] = $provider;

        return $provider;
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

        // These would be core framework providers
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
                'router' => [Router::class],
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
    }

    /**
     * Load environment configuration.
     */
    protected function loadEnvironmentConfiguration(): void
    {
        $envFile = $this->basePath('.env');

        if (file_exists($envFile)) {
            $this->loadEnvFile($envFile);
        }

        $this->environment = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'production';
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
