<?php

declare(strict_types=1);

namespace Plugs\View;

use Exception;
use Plugs\View\Compiler\ViewCompiler;
use Plugs\View\Engines\EngineResolver;
use Plugs\Exceptions\View\ViewException;

class View
{
    protected static array $sharedData = [];
    protected static ?ViewFinder $finder = null;
    protected static ?EngineResolver $engineResolver = null;
    protected static ?ViewCompiler $compiler = null;
    protected static ?string $cachePath = null;
    protected static bool $cacheEnabled = false;
    protected static $authChecker = null;
    protected string $template;
    protected array $data;
    protected ?string $layout = null;
    protected array $sections = [];
    protected array $sectionStack = [];
    protected static ?string $assetPath = null;
    protected static ?string $assetVersion = null;
    protected static bool $assetAutoVersion = false;
    protected array $onceTokens = [];
    protected array $stacks = [];
    protected array $fragments = [];
    protected array $componentData = [];
    protected static array $macros = [];
    protected static array $viewComposers = [];
    protected static array $viewCreators = [];

    public function __construct(string $template, array $data = [])
    {
        $this->template = $template;

        // Apply view creators for this template
        $this->applyViewCreators();

        // Validate data for variable collisions
        $compiler = static::getCompiler();
        $data = $compiler->validateVariables($data);

        $this->data = array_merge(static::$sharedData, $data);

        // Apply view composers for this template
        $this->applyViewComposers();

        // Ensure session is started for CSRF
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    protected function applyViewCreators(): void
    {
        foreach (static::$viewCreators as $pattern => $callback) {
            if ($this->templateMatchesPattern($pattern)) {
                $callback($this);
            }
        }
    }

    protected function applyViewComposers(): void
    {
        foreach (static::$viewComposers as $pattern => $callback) {
            if ($this->templateMatchesPattern($pattern)) {
                $callback($this);
            }
        }
    }

    protected function templateMatchesPattern(string $pattern): bool
    {
        // Simple exact match
        if ($pattern === $this->template) {
            return true;
        }

        // Wildcard matching
        if (str_contains($pattern, '*')) {
            $regex = str_replace('\*', '.*', preg_quote($pattern, '/'));
            return (bool) preg_match("/^{$regex}$/", $this->template);
        }

        return false;
    }

    public static function make(string $template, array $data = []): self
    {
        return new static($template, $data);
    }

    public static function share(string|array $key, mixed $value = null): void
    {
        if (is_array($key)) {
            // Validate shared data too
            if (static::$compiler) {
                $key = static::$compiler->validateVariables($key);
            }
            static::$sharedData = array_merge(static::$sharedData, $key);
        } else {
            if (static::$compiler && in_array($key, static::$compiler->getReservedVariables())) {
                throw new \InvalidArgumentException("Cannot share reserved variable name: $key");
            }
            static::$sharedData[$key] = $value;
        }
    }

    public static function addPath(string $path): void
    {
        static::getFinder()->addPath($path);
    }

    public static function setFinder(ViewFinder $finder): void
    {
        static::$finder = $finder;
    }

    public static function setEngineResolver(EngineResolver $resolver): void
    {
        static::$engineResolver = $resolver;
    }

    public static function setCompiler(ViewCompiler $compiler): void
    {
        static::$compiler = $compiler;
        
        // Also set compiler on the PlugsEngine if available
        if (static::$engineResolver && static::$engineResolver->hasEngine('plugs')) {
            $plugsEngine = static::$engineResolver->resolve('plugs');
            if (method_exists($plugsEngine, 'setCompiler')) {
                $plugsEngine->setCompiler($compiler);
            }
        }
    }

    public static function setCaching(bool $enabled, ?string $cachePath = null): void
    {
        static::$cacheEnabled = $enabled;
        if ($cachePath) {
            static::$cachePath = rtrim($cachePath, '/');
            // Create cache directory if it doesn't exist
            if (!is_dir(static::$cachePath)) {
                mkdir(static::$cachePath, 0755, true);
            }
        }

        // Also configure caching on engines that support it
        if (static::$engineResolver) {
            foreach (['plugs'] as $engineName) {
                if (static::$engineResolver->hasEngine($engineName)) {
                    $engine = static::$engineResolver->resolve($engineName);
                    if (method_exists($engine, 'setCaching')) {
                        $engine->setCaching($enabled, $cachePath);
                    }
                }
            }
        }
    }

    public static function setAuthChecker(callable $checker): void
    {
        static::$authChecker = $checker;
    }

    protected static function getFinder(): ViewFinder
    {
        if (static::$finder === null) {
            static::$finder = new ViewFinder();
        }
        return static::$finder;
    }

    protected static function getEngineResolver(): EngineResolver
    {
        if (static::$engineResolver === null) {
            static::$engineResolver = new EngineResolver();
        }
        return static::$engineResolver;
    }

    protected static function getCompiler(): ViewCompiler
    {
        if (static::$compiler === null) {
            static::$compiler = new ViewCompiler();
        }
        return static::$compiler;
    }

    public static function directive(string $name, callable $handler): void
    {
        static::getCompiler()->directive($name, $handler);
    }

    public function render(): string
    {
        try {
            $templatePath = static::getFinder()->find($this->template);
            $engineResolver = static::getEngineResolver();
            
            // Determine which engine to use
            $engineName = $engineResolver->getEngineFromPath($templatePath);
            $engine = $engineResolver->resolve($engineName);
            
            // Get the rendered content
            $content = $engine->get($templatePath, $this->prepareDataForEngine());
            
            // Handle layout if present
            if ($this->layout) {
                $layoutView = new static($this->layout, array_merge($this->data, [
                    'content' => $content
                ]));
                $layoutView->sections = array_merge($this->sections, $layoutView->sections);
                $layoutView->stacks = $this->stacks;
                $layoutView->fragments = $this->fragments;
                return $layoutView->render();
            }

            return $content;
            
        } catch (Exception $e) {
            throw new ViewException("Template rendering failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Prepare data for the rendering engine.
     */
    protected function prepareDataForEngine(): array
    {
        // Add the view instance to data for template access
        $data = $this->data;
        $data['__view'] = $this;
        
        return $data;
    }

    /**
     * Check if a view exists.
     */
    public static function exists(string $template): bool
    {
        return static::getFinder()->exists($template);
    }

    /**
     * Get all available views.
     */
    public static function getAllViews(): array
    {
        return static::getFinder()->getAllViews();
    }

    /**
     * Get views matching a pattern.
     */
    public static function getViewsMatching(string $pattern): array
    {
        return static::getFinder()->getViewsMatching($pattern);
    }

    // Layout and section methods
    public function extend(string $layout): void
    {
        $this->layout = $layout;
    }

    public function startSection(string $name): void
    {
        if (in_array($name, $this->sectionStack)) {
            throw new ViewException("Section '$name' is already open");
        }
        $this->sectionStack[] = $name;
        ob_start();
    }

    public function endSection(): void
    {
        if (empty($this->sectionStack)) {
            throw new ViewException("No section to end");
        }

        $name = array_pop($this->sectionStack);
        $content = ob_get_clean();

        if ($content === false) {
            throw new ViewException("Failed to capture section content");
        }

        $this->sections[$name] = $content;
    }

    public function endSectionAndShow(): string
    {
        $this->endSection();
        $name = end($this->sectionStack);
        return $this->sections[$name] ?? '';
    }

    public function yieldContent(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    public function include(string $template, array $data = []): string
    {
        $includeView = new static($template, array_merge($this->data, $data));
        return $includeView->render();
    }

    public function csrf(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $token = $_SESSION['csrf_token'];
        return '<input type="hidden" name="_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    public function isAuthenticated(): bool
    {
        if (static::$authChecker) {
            return (static::$authChecker)();
        }

        // Default implementation - check if user session exists
        return isset($_SESSION['user']) || isset($_SESSION['authenticated']);
    }

    // Static method to verify CSRF token
    public static function verifyCsrfToken(?string $token = null): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = $token ?? ($_POST['_token'] ?? $_GET['_token'] ?? '');
        $sessionToken = $_SESSION['csrf_token'] ?? '';

        return !empty($token) && !empty($sessionToken) && hash_equals($sessionToken, $token);
    }

    // Clear compiled cache
    public static function clearCache(): void
    {
        if (static::$cachePath && is_dir(static::$cachePath)) {
            $files = glob(static::$cachePath . '/*.php');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        // Also clear cache on engines that support it
        if (static::$engineResolver) {
            foreach (['plugs'] as $engineName) {
                if (static::$engineResolver->hasEngine($engineName)) {
                    $engine = static::$engineResolver->resolve($engineName);
                    if (method_exists($engine, 'clearCache')) {
                        $engine->clearCache();
                    }
                }
            }
        }

        // Clear finder cache
        static::getFinder()->clearCache();
    }

    public function __toString(): string
    {
        try {
            return $this->render();
        } catch (Exception $e) {
            return "View Error: " . $e->getMessage();
        }
    }

    // Enhanced debug method
    public function debug(): array
    {
        return [
            'template' => $this->template,
            'data' => $this->data,
            'paths' => static::getFinder()->getPaths(),
            'layout' => $this->layout,
            'sections' => array_keys($this->sections),
            'stacks' => array_keys($this->stacks),
            'fragments' => array_keys($this->fragments),
            'cache_enabled' => static::$cacheEnabled,
            'cache_path' => static::$cachePath,
            'reserved_variables' => static::getCompiler()->getReservedVariables(),
            'engines' => static::getEngineResolver()->getEngines(),
            'finder_extensions' => static::getFinder()->getExtensions()
        ];
    }

    // Method to with() for fluent interface
    public function with(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, static::getCompiler()->validateVariables($key));
        } else {
            if (in_array($key, static::getCompiler()->getReservedVariables())) {
                throw new \InvalidArgumentException("Cannot use reserved variable name: $key");
            }
            $this->data[$key] = $value;
        }

        return $this;
    }

    /** Asset Management */

    public static function setAssetPath(string $path): void
    {
        static::$assetPath = rtrim($path, '/');
    }

    public static function setAssetVersion(string $version): void
    {
        static::$assetVersion = $version;
    }

    public static function enableAutoVersioning(bool $enabled = true): void
    {
        static::$assetAutoVersion = $enabled;
    }

    public function asset(string $path): string
    {
        $assetPath = static::$assetPath ?? '';
        $url = rtrim($assetPath, '/') . '/' . ltrim($path, '/');

        // Add version if specified
        if (static::$assetVersion) {
            $url .= '?v=' . static::$assetVersion;
        } elseif (static::$assetAutoVersion && static::$assetPath) {
            // Auto version based on file modification time
            $fullPath = static::$assetPath . '/' . ltrim($path, '/');
            if (file_exists($fullPath)) {
                $url .= '?v=' . filemtime($fullPath);
            }
        }

        return $url;
    }

    public function css(string $path, array $attributes = []): string
    {
        $url = $this->asset($path);
        $attrs = $this->htmlAttributes($attributes);
        return '<link rel="stylesheet" href="' . htmlspecialchars($url) . '"' . $attrs . '>';
    }

    public function js(string $path, array $attributes = []): string
    {
        $url = $this->asset($path);
        $attrs = $this->htmlAttributes($attributes);
        return '<script src="' . htmlspecialchars($url) . '"' . $attrs . '></script>';
    }

    public function img(string $path, array $attributes = []): string
    {
        $url = $this->asset($path);
        $attrs = $this->htmlAttributes($attributes);
        return '<img src="' . htmlspecialchars($url) . '"' . $attrs . '>';
    }

    protected function htmlAttributes(array $attributes): string
    {
        $html = '';
        foreach ($attributes as $key => $value) {
            if (is_bool($value)) {
                if ($value) $html .= ' ' . $key;
            } else {
                $html .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
            }
        }
        return $html;
    }

    public function json(mixed $data): string
    {
        return htmlspecialchars(json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
    }

    public function once(?string $token = null): bool
    {
        $token = $token ?: md5(serialize(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)));

        if (isset($this->onceTokens[$token])) {
            return false;
        }

        $this->onceTokens[$token] = true;
        return true;
    }

    // Stack methods
    public function startPush(string $name): void
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    public function endPush(): void
    {
        $name = array_pop($this->sectionStack);
        $content = ob_get_clean();
        $this->stacks[$name][] = $content;
    }

    public function stack(string $name): string
    {
        return implode('', $this->stacks[$name] ?? []);
    }

    public function prepend(string $name): void
    {
        if (!isset($this->stacks[$name])) {
            $this->stacks[$name] = [];
        }

        $content = ob_get_clean();
        array_unshift($this->stacks[$name], $content);
    }

    // Fragment methods
    public function startFragment(string $name): void
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    public function endFragment(): void
    {
        $name = array_pop($this->sectionStack);
        $content = ob_get_clean();
        $this->fragments[$name] = $content;
    }

    public function fragment(string $name): ?string
    {
        return $this->fragments[$name] ?? null;
    }

    public function hasFragment(string $name): bool
    {
        return isset($this->fragments[$name]);
    }

    // Component methods
    public function component(string $name, array $data = []): void
    {
        $this->componentData[$name] = $data;
        $this->startSection($name);
    }

    public function endComponent(): void
    {
        $this->endSection();
    }

    public function slot(string $name = 'default'): string
    {
        return $this->yieldContent($name, '');
    }

    // Form helpers
    public function formOpen(string $action, string $method = 'POST', array $attributes = []): string
    {
        $attrs = $this->htmlAttributes($attributes);
        $html = '<form action="' . htmlspecialchars($action) . '" method="' . htmlspecialchars(strtoupper($method)) . '"' . $attrs . '>';

        if (strtoupper($method) !== 'GET') {
            $html .= $this->csrf();
        }

        return $html;
    }

    public function formClose(): string
    {
        return '</form>';
    }

    // Macro system
    public static function macro(string $name, callable $macro): void
    {
        static::$macros[$name] = $macro;
    }

    public static function hasMacro(string $name): bool
    {
        return isset(static::$macros[$name]);
    }

    public function __call(string $method, array $parameters)
    {
        if (static::hasMacro($method)) {
            return call_user_func_array(static::$macros[$method]->bindTo($this, static::class), $parameters);
        }

        throw new \BadMethodCallException("Method {$method} does not exist.");
    }

    // View composers and creators
    public static function composer(string|array $views, callable $callback): void
    {
        foreach ((array) $views as $view) {
            static::$viewComposers[$view] = $callback;
        }
    }

    public static function creator(string|array $views, callable $callback): void
    {
        foreach ((array) $views as $view) {
            static::$viewCreators[$view] = $callback;
        }
    }

    // Conditional rendering
    public function when(bool $condition, callable $callback, ?callable $default = null): ?string
    {
        if ($condition) {
            return $callback($this);
        }

        if ($default) {
            return $default($this);
        }

        return null;
    }

    public function unless(bool $condition, callable $callback, ?callable $default = null): ?string
    {
        return $this->when(!$condition, $callback, $default);
    }

    // Error bag for form validation
    public function withErrors(array $errors): self
    {
        return $this->with('errors', $errors);
    }

    // Old input for form repopulation
    public function withInput(array $input): self
    {
        return $this->with('old', $input);
    }

    // Shortcut for rendering JSON
    public function jsonResponse(array $data, int $status = 200): string
    {
        http_response_code($status);
        header('Content-Type: application/json');
        return json_encode($data);
    }

    // Shortcut for redirect responses
    public function redirect(string $url, int $status = 302): string
    {
        http_response_code($status);
        header("Location: $url");
        return '';
    }

    /**
     * Get the view finder instance.
     */
    public static function finder(): ViewFinder
    {
        return static::getFinder();
    }

    /**
     * Get the engine resolver instance.
     */
    public static function engines(): EngineResolver
    {
        return static::getEngineResolver();
    }

    /**
     * Get the compiler instance.
     */
    public static function compiler(): ViewCompiler
    {
        return static::getCompiler();
    }

    /**
     * Register a custom engine.
     */
    public static function registerEngine(string $name, \Closure $resolver): void
    {
        static::getEngineResolver()->register($name, $resolver);
    }

    /**
     * Add namespace support for views.
     */
    public static function addNamespace(string $namespace, string|array $hints): void
    {
        static::getFinder()->addNamespace($namespace, $hints);
    }

    /**
     * Prepend a view path.
     */
    public static function prependPath(string $path): void
    {
        static::getFinder()->prependPath($path);
    }

    /**
     * Add a view file extension.
     */
    public static function addExtension(string $extension): void
    {
        static::getFinder()->addExtension($extension);
    }

    /**
     * Flush all view data and clear caches.
     */
    public static function flush(): void
    {
        static::clearCache();
        static::getFinder()->flush();
        if (static::$engineResolver) {
            static::$engineResolver->clearResolved();
        }
        static::$sharedData = [];
        static::$viewComposers = [];
        static::$viewCreators = [];
        static::$macros = [];
    }
}