<?php

declare(strict_types=1);

namespace Plugs\View;

use Exception;
use Plugs\View\Compiler\ViewCompiler;
use Plugs\Exceptions\View\ViewException;

class View
{
    protected static array $sharedData = [];
    protected static array $paths = [];
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

    public function __construct(string $template, array $data = [])
    {
        $this->template = $template;

        // Validate data for variable collisions
        $compiler = static::getCompiler();
        $data = $compiler->validateVariables($data);

        $this->data = array_merge(static::$sharedData, $data);

        // Ensure session is started for CSRF
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
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
        static::$paths[] = rtrim($path, '/');
    }

    public static function setCompiler(ViewCompiler $compiler): void
    {
        static::$compiler = $compiler;
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
    }

    public static function setAuthChecker(callable $checker): void
    {
        static::$authChecker = $checker;
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
        $templatePath = $this->findTemplate($this->template);

        // Check cache first if enabled
        $cacheKey = null;
        $cacheFile = null;

        if (static::$cacheEnabled && static::$cachePath) {
            $cacheKey = md5($templatePath . filemtime($templatePath));
            $cacheFile = static::$cachePath . '/' . $cacheKey . '.php';

            if (file_exists($cacheFile) && filemtime($cacheFile) >= filemtime($templatePath)) {
                return $this->renderFromCache($cacheFile);
            }
        }

        // Read and compile template
        $content = file_get_contents($templatePath);
        if ($content === false) {
            throw new ViewException("Unable to read template file: {$templatePath}");
        }

        $compiled = static::getCompiler()->compile($content);

        // Save to cache if enabled
        if (static::$cacheEnabled && $cacheFile) {
            file_put_contents($cacheFile, $compiled);
            return $this->renderFromCache($cacheFile);
        }

        return $this->renderCompiled($compiled);
    }

    protected function renderFromCache(string $cacheFile): string
    {
        return $this->renderCompiledFile($cacheFile);
    }

    protected function renderCompiled(string $compiled): string
    {
        // Create temporary file with compiled PHP
        $tempFile = tempnam(sys_get_temp_dir(), 'view_') . '.php';
        if (file_put_contents($tempFile, $compiled) === false) {
            throw new ViewException("Failed to write compiled template");
        }

        try {
            $result = $this->renderCompiledFile($tempFile);
            unlink($tempFile);
            return $result;
        } catch (Exception $e) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            throw $e;
        }
    }

    protected function renderCompiledFile(string $file): string
    {
        try {
            // CRITICAL: Use $__view to avoid variable collision
            // Also extract data but protect special variables
            $__view = $this;
            $__data = $this->data;

            // Extract variables but exclude reserved names
            extract($__data, EXTR_SKIP);

            ob_start();
            include $file;
            $output = ob_get_clean();

            if ($output === false) {
                throw new ViewException("Failed to capture template output");
            }

            // Handle layout if present
            if ($this->layout) {
                $layoutView = new static($this->layout, array_merge($this->data, [
                    'content' => $output
                ]));
                $layoutView->sections = array_merge($this->sections, $layoutView->sections);
                return $layoutView->render();
            }

            return $output;
        } catch (Exception $e) {
            throw new ViewException("Template rendering failed: " . $e->getMessage(), 0, $e);
        }
    }

    protected function findTemplate(string $name): string
    {
        $name = str_replace('.', '/', $name);
        $extensions = ['.plug.php', '.php', '.html'];

        foreach (static::$paths as $path) {
            foreach ($extensions as $ext) {
                $templatePath = $path . '/' . $name . $ext;
                if (file_exists($templatePath)) {
                    return $templatePath;
                }
            }
        }

        throw new ViewException("Template '{$name}' not found in paths: " . implode(', ', static::$paths));
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
            'paths' => static::$paths,
            'layout' => $this->layout,
            'sections' => array_keys($this->sections),
            'cache_enabled' => static::$cacheEnabled,
            'cache_path' => static::$cachePath,
            'reserved_variables' => static::getCompiler()->getReservedVariables()
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

    /** Extras */

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
}
