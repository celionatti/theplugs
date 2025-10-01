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
            $view = View::make('_seo', $data);
            
            if (!empty($data)) {
                $view->seo($data);
            }
            
            return $view->renderMetaTags();
        } catch (\Exception $e) {
            return '<!-- SEO meta tags generation failed: ' . htmlspecialchars($e->getMessage()) . ' -->';
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

        return $title . $separator . $siteName;
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
     * Get social sharing URL for a platform.
     */
    public function getShareUrl(string $platform, string $url, string $title = '', string $description = ''): string
    {
        $url = urlencode($url);
        $title = urlencode($title);
        
        return match($platform) {
            'facebook' => "https://www.facebook.com/sharer/sharer.php?u={$url}",
            'twitter' => "https://twitter.com/intent/tweet?text={$title}&url={$url}",
            'linkedin' => "https://www.linkedin.com/sharing/share-offsite/?url={$url}",
            'whatsapp' => "https://wa.me/?text={$title} {$url}",
            'telegram' => "https://t.me/share/url?url={$url}&text={$title}",
            'pinterest' => "https://pinterest.com/pin/create/button/?url={$url}&description={$title}",
            'reddit' => "https://reddit.com/submit?url={$url}&title={$title}",
            default => ''
        };
    }

    /**
     * Build canonical URL from path.
     */
    public function canonicalUrl(string $path = ''): string
    {
        $baseUrl = rtrim($this->config['canonical_base_url'] ?? '', '/');
        $path = ltrim($path, '/');
        
        return $baseUrl . ($path ? '/' . $path : '');
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