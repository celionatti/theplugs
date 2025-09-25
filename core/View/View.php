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
    protected array $fragments = [];
    protected array $componentData = [];
    protected static array $macros = [];
    protected static array $viewComposers = [];
    protected static array $viewCreators = [];

    // SEO and Meta properties
    protected array $metaTags = [];
    protected array $linkTags = [];
    protected array $structuredData = [];
    protected static array $globalMeta = [];
    protected static array $seoConfig = [
        'site_name' => '',
        'default_title' => '',
        'title_separator' => ' | ',
        'twitter_username' => '',
        'facebook_app_id' => '',
        'default_image' => '',
        'default_description' => '',
        'default_keywords' => [],
        'locale' => 'en_US',
        'canonical_base_url' => ''
    ];

    public function __construct(string $template, array $data = [])
    {
        $this->template = $template;

        // Apply view creators for this template
        $this->applyViewCreators();

        // Validate data for variable collisions
        $compiler = static::getCompiler();
        $data = $compiler->validateVariables($data);

        $this->data = array_merge(static::$sharedData, $data);

        // Initialize meta tags with global defaults
        $this->metaTags = static::$globalMeta;

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

    // SEO Configuration Methods
    public static function setSeoConfig(array $config): void
    {
        static::$seoConfig = array_merge(static::$seoConfig, $config);
    }

    public static function getSeoConfig(?string $key = null): mixed
    {
        if ($key) {
            return static::$seoConfig[$key] ?? null;
        }
        return static::$seoConfig;
    }

    public static function setGlobalMeta(array $meta): void
    {
        static::$globalMeta = array_merge(static::$globalMeta, $meta);
    }

    // Meta Tag Methods
    public function setTitle(string $title, bool $append = true): self
    {
        if ($append && static::$seoConfig['site_name']) {
            $title = $title . static::$seoConfig['title_separator'] . static::$seoConfig['site_name'];
        }

        $this->metaTags['title'] = $title;
        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->metaTags['description'] = $description;
        return $this;
    }

    public function setKeywords(array|string $keywords): self
    {
        if (is_array($keywords)) {
            $keywords = implode(', ', $keywords);
        }
        $this->metaTags['keywords'] = $keywords;
        return $this;
    }

    public function setCanonical(string $url): self
    {
        $this->linkTags['canonical'] = [
            'rel' => 'canonical',
            'href' => $url
        ];
        return $this;
    }

    public function setRobots(string $robots = 'index, follow'): self
    {
        $this->metaTags['robots'] = $robots;
        return $this;
    }

    public function setAuthor(string $author): self
    {
        $this->metaTags['author'] = $author;
        return $this;
    }

    public function setViewport(string $viewport = 'width=device-width, initial-scale=1'): self
    {
        $this->metaTags['viewport'] = $viewport;
        return $this;
    }

    // Open Graph Methods
    public function setOgTitle(string $title): self
    {
        $this->metaTags['og:title'] = $title;
        return $this;
    }

    public function setOgDescription(string $description): self
    {
        $this->metaTags['og:description'] = $description;
        return $this;
    }

    public function setOgImage(string $image, array $properties = []): self
    {
        $this->metaTags['og:image'] = $image;

        if (isset($properties['width'])) {
            $this->metaTags['og:image:width'] = $properties['width'];
        }
        if (isset($properties['height'])) {
            $this->metaTags['og:image:height'] = $properties['height'];
        }
        if (isset($properties['alt'])) {
            $this->metaTags['og:image:alt'] = $properties['alt'];
        }

        return $this;
    }

    public function setOgUrl(string $url): self
    {
        $this->metaTags['og:url'] = $url;
        return $this;
    }

    public function setOgType(string $type = 'website'): self
    {
        $this->metaTags['og:type'] = $type;
        return $this;
    }

    public function setOgSiteName(string $siteName): self
    {
        $this->metaTags['og:site_name'] = $siteName;
        return $this;
    }

    public function setOgLocale(string $locale): self
    {
        $this->metaTags['og:locale'] = $locale;
        return $this;
    }

    // Twitter Card Methods
    public function setTwitterCard(string $type = 'summary_large_image'): self
    {
        $this->metaTags['twitter:card'] = $type;
        return $this;
    }

    public function setTwitterSite(string $username): self
    {
        $this->metaTags['twitter:site'] = $username;
        return $this;
    }

    public function setTwitterCreator(string $username): self
    {
        $this->metaTags['twitter:creator'] = $username;
        return $this;
    }

    public function setTwitterTitle(string $title): self
    {
        $this->metaTags['twitter:title'] = $title;
        return $this;
    }

    public function setTwitterDescription(string $description): self
    {
        $this->metaTags['twitter:description'] = $description;
        return $this;
    }

    public function setTwitterImage(string $image): self
    {
        $this->metaTags['twitter:image'] = $image;
        return $this;
    }

    // Generic meta and link methods
    public function addMeta(string $name, string $content, string $type = 'name'): self
    {
        $this->metaTags[$name] = $content;
        return $this;
    }

    public function addLink(string $rel, string $href, array $attributes = []): self
    {
        $this->linkTags[$rel] = array_merge(['rel' => $rel, 'href' => $href], $attributes);
        return $this;
    }

    // Structured Data Methods
    public function addStructuredData(array $data, string $type = 'application/ld+json'): self
    {
        $this->structuredData[] = [
            'type' => $type,
            'data' => $data
        ];
        return $this;
    }

    public function addBreadcrumb(array $breadcrumbs): self
    {
        $structuredData = [
            "@context" => "https://schema.org",
            "@type" => "BreadcrumbList",
            "itemListElement" => []
        ];

        foreach ($breadcrumbs as $index => $crumb) {
            $structuredData["itemListElement"][] = [
                "@type" => "ListItem",
                "position" => $index + 1,
                "name" => $crumb['name'],
                "item" => $crumb['url'] ?? null
            ];
        }

        return $this->addStructuredData($structuredData);
    }

    public function addArticleSchema(array $article): self
    {
        $structuredData = [
            "@context" => "https://schema.org",
            "@type" => "Article",
            "headline" => $article['headline'],
            "author" => [
                "@type" => "Person",
                "name" => $article['author']
            ],
            "datePublished" => $article['datePublished'],
            "dateModified" => $article['dateModified'] ?? $article['datePublished'],
            "description" => $article['description'] ?? '',
            "image" => $article['image'] ?? null
        ];

        return $this->addStructuredData($structuredData);
    }

    public function addOrganizationSchema(array $org): self
    {
        $structuredData = [
            "@context" => "https://schema.org",
            "@type" => "Organization",
            "name" => $org['name'],
            "url" => $org['url'],
            "logo" => $org['logo'] ?? null,
            "sameAs" => $org['socialMedia'] ?? []
        ];

        return $this->addStructuredData($structuredData);
    }

    // Quick SEO setup method
    public function seo(array $seoData): self
    {
        // Basic meta
        if (isset($seoData['title'])) {
            $this->setTitle($seoData['title']);
        }

        if (isset($seoData['description'])) {
            $this->setDescription($seoData['description']);
        }

        if (isset($seoData['keywords'])) {
            $this->setKeywords($seoData['keywords']);
        }

        if (isset($seoData['canonical'])) {
            $this->setCanonical($seoData['canonical']);
        }

        if (isset($seoData['robots'])) {
            $this->setRobots($seoData['robots']);
        }

        // Open Graph
        if (isset($seoData['og'])) {
            $og = $seoData['og'];

            if (isset($og['title'])) $this->setOgTitle($og['title']);
            if (isset($og['description'])) $this->setOgDescription($og['description']);
            if (isset($og['image'])) $this->setOgImage($og['image'], $og['image_properties'] ?? []);
            if (isset($og['url'])) $this->setOgUrl($og['url']);
            if (isset($og['type'])) $this->setOgType($og['type']);
        }

        // Twitter
        if (isset($seoData['twitter'])) {
            $twitter = $seoData['twitter'];

            if (isset($twitter['card'])) $this->setTwitterCard($twitter['card']);
            if (isset($twitter['title'])) $this->setTwitterTitle($twitter['title']);
            if (isset($twitter['description'])) $this->setTwitterDescription($twitter['description']);
            if (isset($twitter['image'])) $this->setTwitterImage($twitter['image']);
        }

        // Structured data
        if (isset($seoData['structured_data'])) {
            foreach ($seoData['structured_data'] as $data) {
                $this->addStructuredData($data);
            }
        }

        return $this;
    }

    // Render meta tags
    public function renderMetaTags(): string
    {
        $html = '';

        // Title tag
        if (isset($this->metaTags['title'])) {
            $html .= '<title>' . htmlspecialchars($this->metaTags['title']) . '</title>' . "\n";
        }

        // Meta tags
        foreach ($this->metaTags as $name => $content) {
            if ($name === 'title') continue; // Already handled above

            if (str_starts_with($name, 'og:') || str_starts_with($name, 'twitter:')) {
                $html .= '<meta property="' . htmlspecialchars($name) . '" content="' . htmlspecialchars($content) . '">' . "\n";
            } else {
                $html .= '<meta name="' . htmlspecialchars($name) . '" content="' . htmlspecialchars($content) . '">' . "\n";
            }
        }

        // Link tags
        foreach ($this->linkTags as $link) {
            $html .= '<link';
            foreach ($link as $attr => $value) {
                $html .= ' ' . htmlspecialchars($attr) . '="' . htmlspecialchars($value) . '"';
            }
            $html .= '>' . "\n";
        }

        // Structured data
        foreach ($this->structuredData as $data) {
            $html .= '<script type="' . htmlspecialchars($data['type']) . '">' . "\n";
            $html .= json_encode($data['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
            $html .= '</script>' . "\n";
        }

        return $html;
    }

    // Method to get current page URL (helper for canonical URLs)
    public function getCurrentUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        return $protocol . '://' . $host . $uri;
    }

    // Auto-generate SEO data from content
    public function autoSeo(array $content = []): self
    {
        // Use provided content or extract from template data
        $content = array_merge($this->data, $content);

        // Auto-generate title
        if (!isset($this->metaTags['title']) && isset($content['title'])) {
            $this->setTitle($content['title']);
        }

        // Auto-generate description
        if (!isset($this->metaTags['description'])) {
            if (isset($content['excerpt'])) {
                $this->setDescription($content['excerpt']);
            } elseif (isset($content['content'])) {
                // Generate description from content
                $description = strip_tags($content['content']);
                $description = substr($description, 0, 160);
                $this->setDescription($description);
            }
        }

        // Auto-set canonical URL
        if (!isset($this->linkTags['canonical'])) {
            $this->setCanonical($this->getCurrentUrl());
        }

        // Auto-set Open Graph data
        if (isset($this->metaTags['title']) && !isset($this->metaTags['og:title'])) {
            $this->setOgTitle($this->metaTags['title']);
        }

        if (isset($this->metaTags['description']) && !isset($this->metaTags['og:description'])) {
            $this->setOgDescription($this->metaTags['description']);
        }

        if (!isset($this->metaTags['og:url'])) {
            $this->setOgUrl($this->getCurrentUrl());
        }

        return $this;
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
                $layoutView->stacks = $this->stacks;
                $layoutView->fragments = $this->fragments;
                // Transfer SEO data to layout
                $layoutView->metaTags = array_merge($layoutView->metaTags, $this->metaTags);
                $layoutView->linkTags = array_merge($layoutView->linkTags, $this->linkTags);
                $layoutView->structuredData = array_merge($layoutView->structuredData, $this->structuredData);
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
            'stacks' => array_keys($this->stacks),
            'fragments' => array_keys($this->fragments),
            'cache_enabled' => static::$cacheEnabled,
            'cache_path' => static::$cachePath,
            'reserved_variables' => static::getCompiler()->getReservedVariables(),
            'meta_tags' => $this->metaTags,
            'link_tags' => $this->linkTags,
            'structured_data' => count($this->structuredData) . ' items'
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
     * Render social sharing buttons
     */
    public function renderSocialShareButtons(array $options = []): string
    {
        $url = $options['url'] ?? $this->getCurrentUrl();
        $title = $options['title'] ?? ($this->metaTags['title'] ?? 'Check this out!');
        $description = $options['description'] ?? ($this->metaTags['description'] ?? '');
        $image = $options['image'] ?? ($this->metaTags['og:image'] ?? '');

        $platforms = $options['platforms'] ?? ['facebook', 'twitter', 'linkedin', 'whatsapp'];

        $html = '<div class="social-share-buttons">';

        foreach ($platforms as $platform) {
            switch ($platform) {
                case 'facebook':
                    $shareUrl = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($url);
                    $html .= '<a href="' . $shareUrl . '" target="_blank" rel="noopener" class="share-facebook">Share on Facebook</a>';
                    break;

                case 'twitter':
                    $twitterText = $title . ($description ? ' - ' . substr($description, 0, 100) : '');
                    $shareUrl = 'https://twitter.com/intent/tweet?text=' . urlencode($twitterText) . '&url=' . urlencode($url);
                    $html .= '<a href="' . $shareUrl . '" target="_blank" rel="noopener" class="share-twitter">Share on Twitter</a>';
                    break;

                case 'linkedin':
                    $shareUrl = 'https://www.linkedin.com/sharing/share-offsite/?url=' . urlencode($url);
                    $html .= '<a href="' . $shareUrl . '" target="_blank" rel="noopener" class="share-linkedin">Share on LinkedIn</a>';
                    break;

                case 'whatsapp':
                    $whatsappText = $title . ' ' . $url;
                    $shareUrl = 'https://wa.me/?text=' . urlencode($whatsappText);
                    $html .= '<a href="' . $shareUrl . '" target="_blank" rel="noopener" class="share-whatsapp">Share on WhatsApp</a>';
                    break;
            }
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Generate FAQ structured data
     */
    public function addFaqSchema(array $faqs): self
    {
        $structuredData = [
            "@context" => "https://schema.org",
            "@type" => "FAQPage",
            "mainEntity" => []
        ];

        foreach ($faqs as $faq) {
            $structuredData["mainEntity"][] = [
                "@type" => "Question",
                "name" => $faq['question'],
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text" => $faq['answer']
                ]
            ];
        }

        return $this->addStructuredData($structuredData);
    }

    /**
     * Generate HowTo structured data
     */
    public function addHowToSchema(array $howTo): self
    {
        $structuredData = [
            "@context" => "https://schema.org",
            "@type" => "HowTo",
            "name" => $howTo['name'],
            "description" => $howTo['description'],
            "image" => $howTo['image'] ?? null,
            "totalTime" => $howTo['totalTime'] ?? null,
            "estimatedCost" => $howTo['estimatedCost'] ?? null,
            "supply" => $howTo['supply'] ?? [],
            "tool" => $howTo['tool'] ?? [],
            "step" => []
        ];

        foreach ($howTo['steps'] as $index => $step) {
            $structuredData["step"][] = [
                "@type" => "HowToStep",
                "position" => $index + 1,
                "name" => $step['name'],
                "text" => $step['text'],
                "image" => $step['image'] ?? null,
                "url" => $step['url'] ?? null
            ];
        }

        return $this->addStructuredData($structuredData);
    }

    /**
     * Generate Review/Rating structured data
     */
    public function addReviewSchema(array $review): self
    {
        $structuredData = [
            "@context" => "https://schema.org",
            "@type" => "Review",
            "reviewRating" => [
                "@type" => "Rating",
                "ratingValue" => $review['rating'],
                "bestRating" => $review['bestRating'] ?? 5,
                "worstRating" => $review['worstRating'] ?? 1
            ],
            "author" => [
                "@type" => "Person",
                "name" => $review['author']
            ],
            "reviewBody" => $review['body'],
            "datePublished" => $review['datePublished']
        ];

        if (isset($review['itemReviewed'])) {
            $structuredData["itemReviewed"] = $review['itemReviewed'];
        }

        return $this->addStructuredData($structuredData);
    }

    /**
     * Generate Event structured data
     */
    public function addEventSchema(array $event): self
    {
        $structuredData = [
            "@context" => "https://schema.org",
            "@type" => "Event",
            "name" => $event['name'],
            "description" => $event['description'],
            "startDate" => $event['startDate'],
            "endDate" => $event['endDate'] ?? null,
            "eventAttendanceMode" => $event['attendanceMode'] ?? "https://schema.org/OfflineEventAttendanceMode",
            "eventStatus" => $event['status'] ?? "https://schema.org/EventScheduled",
            "location" => [
                "@type" => "Place",
                "name" => $event['location']['name'] ?? '',
                "address" => $event['location']['address'] ?? ''
            ],
            "image" => $event['image'] ?? null,
            "organizer" => [
                "@type" => "Organization",
                "name" => $event['organizer'] ?? '',
                "url" => $event['organizerUrl'] ?? ''
            ]
        ];

        if (isset($event['offers'])) {
            $structuredData["offers"] = [
                "@type" => "Offer",
                "price" => $event['offers']['price'],
                "priceCurrency" => $event['offers']['currency'] ?? 'USD',
                "availability" => $event['offers']['availability'] ?? "https://schema.org/InStock",
                "url" => $event['offers']['url'] ?? ''
            ];
        }

        return $this->addStructuredData($structuredData);
    }

    /**
     * Generate LocalBusiness structured data
     */
    public function addLocalBusinessSchema(array $business): self
    {
        $structuredData = [
            "@context" => "https://schema.org",
            "@type" => $business['type'] ?? "LocalBusiness",
            "name" => $business['name'],
            "description" => $business['description'] ?? '',
            "url" => $business['url'],
            "telephone" => $business['telephone'] ?? '',
            "email" => $business['email'] ?? '',
            "image" => $business['image'] ?? null,
            "address" => [
                "@type" => "PostalAddress",
                "streetAddress" => $business['address']['street'] ?? '',
                "addressLocality" => $business['address']['city'] ?? '',
                "addressRegion" => $business['address']['state'] ?? '',
                "postalCode" => $business['address']['zip'] ?? '',
                "addressCountry" => $business['address']['country'] ?? ''
            ],
            "geo" => [
                "@type" => "GeoCoordinates",
                "latitude" => $business['coordinates']['lat'] ?? null,
                "longitude" => $business['coordinates']['lng'] ?? null
            ],
            "openingHours" => $business['hours'] ?? [],
            "priceRange" => $business['priceRange'] ?? ''
        ];

        if (isset($business['aggregateRating'])) {
            $structuredData["aggregateRating"] = [
                "@type" => "AggregateRating",
                "ratingValue" => $business['aggregateRating']['rating'],
                "reviewCount" => $business['aggregateRating']['reviewCount'],
                "bestRating" => $business['aggregateRating']['bestRating'] ?? 5,
                "worstRating" => $business['aggregateRating']['worstRating'] ?? 1
            ];
        }

        return $this->addStructuredData($structuredData);
    }

    /**
     * Generate Website/WebSite structured data with search functionality
     */
    public function addWebsiteSchema(array $website): self
    {
        $structuredData = [
            "@context" => "https://schema.org",
            "@type" => "WebSite",
            "name" => $website['name'],
            "url" => $website['url'],
            "description" => $website['description'] ?? ''
        ];

        // Add search functionality if provided
        if (isset($website['searchUrl'])) {
            $structuredData["potentialAction"] = [
                "@type" => "SearchAction",
                "target" => [
                    "@type" => "EntryPoint",
                    "urlTemplate" => $website['searchUrl'] . "?q={search_term_string}"
                ],
                "query-input" => "required name=search_term_string"
            ];
        }

        return $this->addStructuredData($structuredData);
    }

    /**
     * Set multiple meta tags at once
     */
    public function setMultipleMeta(array $metaTags): self
    {
        foreach ($metaTags as $name => $content) {
            $this->metaTags[$name] = $content;
        }

        return $this;
    }

    /**
     * Add preload link for critical resources
     */
    public function addPreload(string $href, string $as, array $attributes = []): self
    {
        $linkAttributes = array_merge([
            'rel' => 'preload',
            'href' => $href,
            'as' => $as
        ], $attributes);

        $this->linkTags['preload_' . md5($href)] = $linkAttributes;

        return $this;
    }

    /**
     * Add prefetch link for future navigation
     */
    public function addPrefetch(string $href, array $attributes = []): self
    {
        $linkAttributes = array_merge([
            'rel' => 'prefetch',
            'href' => $href
        ], $attributes);

        $this->linkTags['prefetch_' . md5($href)] = $linkAttributes;

        return $this;
    }

    /**
     * Add DNS prefetch for external domains
     */
    public function addDnsPrefetch(string $href): self
    {
        $this->linkTags['dns-prefetch_' . md5($href)] = [
            'rel' => 'dns-prefetch',
            'href' => $href
        ];

        return $this;
    }

    /**
     * Set alternate language versions
     */
    public function addAlternate(string $href, string $hreflang, ?string $type = null): self
    {
        $attributes = [
            'rel' => 'alternate',
            'href' => $href,
            'hreflang' => $hreflang
        ];

        if ($type) {
            $attributes['type'] = $type;
        }

        $this->linkTags['alternate_' . $hreflang] = $attributes;

        return $this;
    }

    /**
     * Add RSS/Atom feed link
     */
    public function addFeed(string $href, string $title, string $type = 'application/rss+xml'): self
    {
        $this->linkTags['feed_' . md5($href)] = [
            'rel' => 'alternate',
            'type' => $type,
            'href' => $href,
            'title' => $title
        ];

        return $this;
    }

    /**
     * Generate pagination meta tags for paginated content
     */
    public function addPagination(array $pagination): self
    {
        if (isset($pagination['prev'])) {
            $this->linkTags['prev'] = [
                'rel' => 'prev',
                'href' => $pagination['prev']
            ];
        }

        if (isset($pagination['next'])) {
            $this->linkTags['next'] = [
                'rel' => 'next',
                'href' => $pagination['next']
            ];
        }

        // Add page number to title if on a paginated page
        if (isset($pagination['current']) && $pagination['current'] > 1) {
            $currentTitle = $this->metaTags['title'] ?? '';
            $this->metaTags['title'] = $currentTitle . ' - Page ' . $pagination['current'];

            // Update Open Graph title too
            if (isset($this->metaTags['og:title'])) {
                $this->metaTags['og:title'] = $this->metaTags['og:title'] . ' - Page ' . $pagination['current'];
            }
        }

        return $this;
    }

    /**
     * Add mobile-specific meta tags
     */
    public function addMobileMeta(): self
    {
        $this->metaTags['mobile-web-app-capable'] = 'yes';
        $this->metaTags['mobile-web-app-status-bar-style'] = 'black-translucent';
        $this->metaTags['format-detection'] = 'telephone=no';

        return $this;
    }

    /**
     * Add Apple-specific meta tags
     */
    public function addAppleMeta(array $apple = []): self
    {
        $defaults = [
            'apple-mobile-web-app-capable' => 'yes',
            'apple-mobile-web-app-status-bar-style' => 'black-translucent',
            'apple-mobile-web-app-title' => $this->metaTags['title'] ?? 'App'
        ];

        $appleMeta = array_merge($defaults, $apple);

        foreach ($appleMeta as $name => $content) {
            $this->metaTags[$name] = $content;
        }

        // Add Apple touch icons if provided
        if (isset($apple['touch_icons'])) {
            foreach ($apple['touch_icons'] as $size => $href) {
                $this->linkTags["apple-touch-icon-{$size}"] = [
                    'rel' => 'apple-touch-icon',
                    'sizes' => $size,
                    'href' => $href
                ];
            }
        }

        return $this;
    }

    /**
     * Add Microsoft-specific meta tags
     */
    public function addMicrosoftMeta(array $microsoft = []): self
    {
        $defaults = [
            'msapplication-TileColor' => '#000000',
            'msapplication-config' => '/browserconfig.xml'
        ];

        $microsoftMeta = array_merge($defaults, $microsoft);

        foreach ($microsoftMeta as $name => $content) {
            $this->metaTags[$name] = $content;
        }

        return $this;
    }

    /**
     * Generate critical CSS inline
     */
    public function addCriticalCss(string $css): self
    {
        $this->sections['critical_css'] = '<style>' . $css . '</style>';

        return $this;
    }

    /**
     * Add analytics tracking codes
     */
    public function addAnalytics(array $analytics): self
    {
        $scripts = [];

        // Google Analytics 4
        if (isset($analytics['google_analytics'])) {
            $ga4Id = $analytics['google_analytics'];
            $scripts[] = "<script async src=\"https://www.googletagmanager.com/gtag/js?id={$ga4Id}\"></script>";
            $scripts[] = "<script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{$ga4Id}');
        </script>";
        }

        // Google Tag Manager
        if (isset($analytics['google_tag_manager'])) {
            $gtmId = $analytics['google_tag_manager'];
            $scripts[] = "<script>
            (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
            new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
            'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
            })(window,document,'script','dataLayer','{$gtmId}');
        </script>";
        }

        // Facebook Pixel
        if (isset($analytics['facebook_pixel'])) {
            $pixelId = $analytics['facebook_pixel'];
            $scripts[] = "<script>
            !function(f,b,e,v,n,t,s)
            {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)}(window, document,'script',
            'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', '{$pixelId}');
            fbq('track', 'PageView');
        </script>";
        }

        $this->sections['analytics'] = implode("\n", $scripts);

        return $this;
    }

    /**
     * Validate SEO setup and return suggestions
     */
    public function validateSeo(): array
    {
        $issues = [];
        $warnings = [];
        $suggestions = [];

        // Check title
        if (!isset($this->metaTags['title'])) {
            $issues[] = 'Missing page title';
        } else {
            $titleLength = strlen($this->metaTags['title']);
            if ($titleLength > 60) {
                $warnings[] = "Title too long ({$titleLength} chars). Recommended: 50-60 characters.";
            } elseif ($titleLength < 30) {
                $warnings[] = "Title too short ({$titleLength} chars). Recommended: 30-60 characters.";
            }
        }

        // Check description
        if (!isset($this->metaTags['description'])) {
            $issues[] = 'Missing meta description';
        } else {
            $descLength = strlen($this->metaTags['description']);
            if ($descLength > 160) {
                $warnings[] = "Description too long ({$descLength} chars). Recommended: 120-160 characters.";
            } elseif ($descLength < 120) {
                $warnings[] = "Description too short ({$descLength} chars). Recommended: 120-160 characters.";
            }
        }

        // Check Open Graph
        $requiredOg = ['og:title', 'og:description', 'og:image', 'og:url'];
        foreach ($requiredOg as $tag) {
            if (!isset($this->metaTags[$tag])) {
                $suggestions[] = "Consider adding {$tag} for better social sharing";
            }
        }

        // Check structured data
        if (empty($this->structuredData)) {
            $suggestions[] = 'Consider adding structured data for better search results';
        }

        // Check canonical URL
        if (!isset($this->linkTags['canonical'])) {
            $suggestions[] = 'Consider adding canonical URL to prevent duplicate content issues';
        }

        return [
            'issues' => $issues,
            'warnings' => $warnings,
            'suggestions' => $suggestions,
            'score' => $this->calculateSeoScore($issues, $warnings)
        ];
    }

    /**
     * Calculate SEO score based on issues and warnings
     */
    protected function calculateSeoScore(array $issues, array $warnings): int
    {
        $score = 100;
        $score -= count($issues) * 20; // Critical issues
        $score -= count($warnings) * 10; // Warnings

        return max(0, $score);
    }

    /**
     * Export SEO data for external tools
     */
    public function exportSeoData(): array
    {
        return [
            'meta_tags' => $this->metaTags,
            'link_tags' => $this->linkTags,
            'structured_data' => $this->structuredData,
            'validation' => $this->validateSeo()
        ];
    }
}
