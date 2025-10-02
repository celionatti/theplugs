<?php

declare(strict_types=1);

namespace Plugs\Services\Providers;

use Plugs\Config;
use Plugs\View\View;
use Plugs\SEO\SeoHelper;
use Plugs\Services\ServiceProvider;
use Plugs\View\Compiler\ViewCompiler;

class ViewSeoServiceProvider extends ServiceProvider
{
    /**
     * Register SEO services in the container.
     */
    public function register(): void
    {
        $this->registerSeoConfig();
        $this->registerSeoHelper();
        $this->ensureCompiler();
        $this->registerSeoDirectives();
    }

    /**
     * Ensure ViewCompiler exists and is configured.
     */
    protected function ensureCompiler(): void
    {
        if (!$this->container->has(ViewCompiler::class)) {
            $this->container->singleton(ViewCompiler::class, function () {
                return new ViewCompiler();
            });
        }
    }

    /**
     * Register SEO configuration as a singleton.
     */
    protected function registerSeoConfig(): void
    {
        $this->singleton('seo.config', function ($container) {
            return $this->getDefaultSeoConfig();
        });
    }

    /**
     * Register SEO helper service.
     */
    protected function registerSeoHelper(): void
    {
        $this->singleton('seo', function ($container) {
            $config = $container->get('seo.config');
            return new SeoHelper($config);
        });
    }

    /**
     * Bootstrap SEO services.
     */
    public function boot(): void
    {
        $this->configureSeoDefaults();
        $this->setGlobalMetaTags();
        $this->shareConfigWithViews();
        $this->registerViewComposers();
    }

    /**
     * Configure SEO defaults from config files and environment.
     */
    protected function configureSeoDefaults(): void
    {
        // Get base configuration
        $seoConfig = $this->getDefaultSeoConfig();

        // Override with user configuration if available
        try {
            $userSeoConfig = Config::get('seo', []);
            if (is_array($userSeoConfig)) {
                $seoConfig = $this->deepMerge($seoConfig, $userSeoConfig);
            }
        } catch (\Exception $e) {
            // Use defaults
        }

        // Apply environment-specific overrides
        $seoConfig['canonical_base_url'] = $seoConfig['canonical_base_url']
            ?: Config::get('app.url', $this->getCurrentUrl());

        // Ensure URLs are properly formatted
        $seoConfig['canonical_base_url'] = rtrim($seoConfig['canonical_base_url'], '/');

        // Set the configuration in View class
        View::setSeoConfig($seoConfig);

        // Update the container binding
        $this->instance('seo.config', $seoConfig);
    }

    /**
     * Set global meta tags that appear on all pages.
     */
    protected function setGlobalMetaTags(): void
    {
        $globalMeta = $this->getDefaultGlobalMeta();

        try {
            $configGlobalMeta = Config::get('seo.global_meta', []);
            if (is_array($configGlobalMeta)) {
                $globalMeta = array_merge($globalMeta, $configGlobalMeta);
            }
        } catch (\Exception $e) {
            // Use defaults
        }

        // Auto-noindex for non-production environments
        $env = Config::get('app.env', 'production');
        $noindexEnvs = $this->container->get('seo.config')['noindex_environments'] ?? ['local', 'development', 'staging'];

        if (in_array($env, $noindexEnvs, true)) {
            $globalMeta['robots'] = 'noindex, nofollow';
        }

        // Set the global meta tags
        View::setGlobalMeta($globalMeta);
    }

    /**
     * Get default global meta tags.
     */
    protected function getDefaultGlobalMeta(): array
    {
        return [
            'viewport' => 'width=device-width, initial-scale=1',
            'robots' => 'index, follow',
            'generator' => 'Plugs Framework',
            'charset' => 'UTF-8',
            'X-UA-Compatible' => 'IE=edge',
        ];
    }

    /**
     * Register additional SEO-related directives.
     */
    protected function registerSeoDirectives(): void
    {
        try {
            $compiler = $this->container->get(ViewCompiler::class);

            // Set the compiler on View class
            View::setCompiler($compiler);

            // @currentUrl - Get current page URL
            $compiler->directive('currentUrl', function ($exp) {
                return '<?php echo htmlspecialchars($__view->getCurrentUrl(), ENT_QUOTES, "UTF-8"); ?>';
            });

            // @pageTitle - Smart title generation with site name
            $compiler->directive('pageTitle', function ($exp) {
                if ($exp) {
                    return "<?php \$__view->setTitle($exp); ?>";
                }
                return "<?php echo htmlspecialchars(\$__view->metaTags['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>";
            });

            // @siteName - Get site name from config
            $compiler->directive('siteName', function ($exp) {
                return "<?php echo htmlspecialchars(\$seoConfig['site_name'] ?? 'Website', ENT_QUOTES, 'UTF-8'); ?>";
            });

            // @metaDescription - Set meta description
            $compiler->directive('metaDescription', function ($exp) {
                return "<?php \$__view->setDescription($exp); ?>";
            });

            // @metaKeywords - Set meta keywords
            $compiler->directive('metaKeywords', function ($exp) {
                return "<?php \$__view->setKeywords($exp); ?>";
            });

            // @ogImage - Set Open Graph image with properties
            $compiler->directive('ogImage', function ($exp) {
                return "<?php \$__view->setOgImage($exp); ?>";
            });

            // @socialShare - Generate social sharing buttons
            $compiler->directive('socialShare', function ($exp) {
                return '<?php echo $__view->renderSocialShareButtons(' . ($exp ?: '[]') . '); ?>';
            });

            // @noindex - Set noindex for the page
            $compiler->directive('noindex', function ($exp) {
                return "<?php \$__view->setRobots('noindex, nofollow'); ?>";
            });

            // @preload - Add preload link (href, as, [attributes])
            $compiler->directive('preload', function ($exp) {
                return "<?php call_user_func_array([\$__view, 'addPreload'], [$exp]); ?>";
            });

            // @prefetch - Add prefetch link for future navigation
            $compiler->directive('prefetch', function ($exp) {
                return "<?php \$__view->addPrefetch($exp); ?>";
            });

            // @dnsPrefetch - Add DNS prefetch for external domains
            $compiler->directive('dnsPrefetch', function ($exp) {
                return "<?php \$__view->addDnsPrefetch($exp); ?>";
            });

            // @hreflang - Add alternate language versions (href, lang)
            $compiler->directive('hreflang', function ($exp) {
                return "<?php call_user_func_array([\$__view, 'addAlternate'], [$exp]); ?>";
            });

            // @feed - Add RSS/Atom feed link (href, title, type)
            $compiler->directive('feed', function ($exp) {
                return "<?php call_user_func_array([\$__view, 'addFeed'], [$exp]); ?>";
            });

            // @faqSchema - Generate FAQ structured data
            $compiler->directive('faqSchema', function ($exp) {
                return "<?php \$__view->addFaqSchema($exp); ?>";
            });

            // @howToSchema - Generate HowTo structured data
            $compiler->directive('howToSchema', function ($exp) {
                return "<?php \$__view->addHowToSchema($exp); ?>";
            });

            // @reviewSchema - Generate Review structured data
            $compiler->directive('reviewSchema', function ($exp) {
                return "<?php \$__view->addReviewSchema($exp); ?>";
            });

            // @eventSchema - Generate Event structured data
            $compiler->directive('eventSchema', function ($exp) {
                return "<?php \$__view->addEventSchema($exp); ?>";
            });

            // @localBusinessSchema - Generate LocalBusiness structured data
            $compiler->directive('localBusinessSchema', function ($exp) {
                return "<?php \$__view->addLocalBusinessSchema($exp); ?>";
            });

            // @websiteSchema - Generate Website structured data
            $compiler->directive('websiteSchema', function ($exp) {
                return "<?php \$__view->addWebsiteSchema($exp); ?>";
            });

            // @pagination - Add pagination meta
            $compiler->directive('pagination', function ($exp) {
                return "<?php \$__view->addPagination($exp); ?>";
            });

            // @analytics - Add analytics tracking
            $compiler->directive('analytics', function ($exp) {
                return "<?php \$__view->addAnalytics(\$seoConfig['analytics'] ?? []); ?>";
            });

            // @seoValidate - Validate SEO and output results (for debugging)
            $compiler->directive('seoValidate', function ($exp) {
                return "<?php if (\$appDebug ?? false) { \$validation = \$__view->validateSeo(); echo '<!-- SEO Validation: Score ' . \$validation['score'] . '/100 -->'; } ?>";
            });
        } catch (\Exception $e) {
            // Silently fail but log if logger is available
            if ($this->container->has('logger')) {
                $this->container->get('logger')->error(
                    'Failed to register SEO directives: ' . $e->getMessage()
                );
            }
        }
    }

    /**
     * Share SEO configuration and helpers with all views.
     */
    protected function shareConfigWithViews(): void
    {
        try {
            $seoConfig = $this->container->get('seo.config');
            $seoHelper = $this->container->get('seo');

            // Share SEO configuration and helper
            View::share('seoConfig', $seoConfig);
            View::share('seo', $seoHelper);

            // Share common SEO data for easy access
            View::share('siteName', $seoConfig['site_name'] ?? 'Website');
            View::share('defaultImage', $seoConfig['default_image'] ?? '');
            View::share('twitterUsername', $seoConfig['twitter_username'] ?? '');
            View::share('canonicalBaseUrl', $seoConfig['canonical_base_url'] ?? '');
        } catch (\Exception $e) {
            // Fail gracefully
        }
    }

    /**
     * Register view composers for automatic SEO data.
     */
    protected function registerViewComposers(): void
    {
        // Auto-compose SEO data for all views
        View::composer('*', function ($view) {
            $seoConfig = $this->container->get('seo.config');

            // Auto-set site name in OG if not already set
            if (!isset($view->metaTags['og:site_name']) && isset($seoConfig['site_name'])) {
                $view->setOgSiteName($seoConfig['site_name']);
            }

            // Auto-set OG locale if not already set
            if (!isset($view->metaTags['og:locale']) && isset($seoConfig['locale'])) {
                $view->setOgLocale($seoConfig['locale']);
            }

            // Auto-set Twitter username if configured
            if (!isset($view->metaTags['twitter:site']) && !empty($seoConfig['twitter_username'])) {
                $view->setTwitterSite($seoConfig['twitter_username']);
            }

            // Auto-set Twitter card type
            if (!isset($view->metaTags['twitter:card'])) {
                $view->setTwitterCard($seoConfig['twitter_defaults']['card'] ?? 'summary_large_image');
            }

            // Auto-set favicon if configured
            if (!empty($seoConfig['default_favicon'])) {
                $view->addLink('icon', $seoConfig['default_favicon'], ['type' => 'image/x-icon']);
            }
        });
    }

    /**
     * Get default SEO configuration.
     */
    protected function getDefaultSeoConfig(): array
    {
        $appName = Config::get('app.name', 'Plugs Framework');
        $appUrl = Config::get('app.url', '');

        return [
            // Basic site information
            'site_name' => $appName,
            'default_title' => 'Welcome to ' . $appName,
            'title_separator' => ' | ',
            'title_format' => '{title}{separator}{site_name}',
            'default_description' => 'Discover amazing content and features on our website.',
            'default_keywords' => ['website', 'content', 'services'],

            // URLs and assets
            'canonical_base_url' => $appUrl,
            'default_image' => '/images/og-default.jpg',
            'default_favicon' => '/favicon.ico',
            'default_image_width' => 1200,
            'default_image_height' => 630,

            // Social media
            'twitter_username' => '',
            'facebook_app_id' => '',
            'facebook_page_id' => '',
            'instagram_username' => '',
            'linkedin_company' => '',
            'youtube_channel' => '',

            // Localization
            'locale' => 'en_US',
            'language' => 'en',
            'country' => 'US',
            'alternate_languages' => [],

            // Organization info for structured data
            'organization' => [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => $appName,
                'url' => $appUrl,
                'logo' => $appUrl . '/images/logo.png',
                'description' => '',
                'sameAs' => []
            ],

            // Global meta tags (applied to all pages)
            'global_meta' => $this->getDefaultGlobalMeta(),

            // Default Open Graph settings
            'og_defaults' => [
                'type' => 'website',
                'locale' => 'en_US',
                'site_name' => $appName
            ],

            // Default Twitter Card settings
            'twitter_defaults' => [
                'card' => 'summary_large_image'
            ],

            // Analytics and tracking (configure in config/seo.php)
            'analytics' => [
                'google_analytics' => '',
                'google_tag_manager' => '',
                'facebook_pixel' => ''
            ],

            // Verification codes
            'google_site_verification' => '',
            'bing_site_verification' => '',

            // SEO features
            'auto_canonical' => true,
            'auto_og_generation' => true,
            'auto_twitter_generation' => true,
            'trailing_slash' => false,

            // Performance
            'preload_critical_assets' => [],
            'dns_prefetch_domains' => [],
            'preconnect_domains' => [],

            // Security
            'referrer_policy' => 'strict-origin-when-cross-origin',

            // Robots meta
            'default_robots' => 'index, follow',
            'noindex_environments' => ['local', 'development', 'staging'],
        ];
    }

    /**
     * Get current URL helper.
     */
    protected function getCurrentUrl(): string
    {
        $protocol = $this->isHttps() ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $protocol . '://' . $host;
    }

    /**
     * Check if the current request is HTTPS.
     */
    protected function isHttps(): bool
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    }

    /**
     * Deep merge two arrays recursively.
     */
    protected function deepMerge(array $array1, array $array2): array
    {
        $merged = $array1;

        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->deepMerge($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }
}
