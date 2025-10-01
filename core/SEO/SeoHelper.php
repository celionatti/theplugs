<?php

declare(strict_types=1);

namespace Plugs\SEO;

use Plugs\Config;
use Plugs\View\View;

class SeoHelper
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get configuration value using dot notation.
     */
    public function getConfig(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->config;
        }

        return $this->getNestedValue($this->config, $key, $default);
    }

    /**
     * Set or merge configuration.
     */
    public function setConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
        View::setSeoConfig($this->config);
    }

    /**
     * Generate meta tags for a page.
     */
    public function generateMetaTags(array $data = []): string
    {
        try {
            $view = new \ReflectionClass(View::class);
            $instance = $view->newInstanceWithoutConstructor();
            
            if (!empty($data)) {
                $instance->seo($data);
            }
            
            return $instance->renderMetaTags();
        } catch (\Exception $e) {
            return '<!-- SEO meta tags generation failed -->';
        }
    }

    /**
     * Generate title with proper formatting.
     */
    public function generateTitle(string $title, bool $includeSiteName = true): string
    {
        if (!$includeSiteName) {
            return $title;
        }

        $separator = $this->config['title_separator'] ?? ' | ';
        $siteName = $this->config['site_name'] ?? 'Website';
        $format = $this->config['title_format'] ?? '{title}{separator}{site_name}';

        return str_replace(
            ['{title}', '{separator}', '{site_name}'],
            [$title, $separator, $siteName],
            $format
        );
    }

    /**
     * Check if current environment should be noindexed.
     */
    public function shouldNoIndex(): bool
    {
        $env = Config::get('app.env', 'production');
        $noindexEnvs = $this->config['noindex_environments'] ?? [];
        
        return in_array($env, $noindexEnvs, true);
    }

    /**
     * Get nested value from array using dot notation.
     */
    private function getNestedValue(array $array, string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $array;
        
        foreach ($keys as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }
        
        return $value;
    }
}