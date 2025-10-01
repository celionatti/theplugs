# SEO Best Practices & Implementation Guide

## Installation & Setup

### 1. Register the Service Provider

Add the `ViewSeoServiceProvider` to your `config/app.php` or bootstrap file:

```php
$app->register(App\Providers\ViewSeoServiceProvider::class);
```

### 2. Create SEO Configuration

Create `config/seo.php` with your custom settings (see the configuration example).

### 3. Set Environment Variables

Add these to your `.env` file:

```env
APP_NAME="Your Website Name"
APP_URL=https://yourdomain.com

GOOGLE_ANALYTICS_ID=G-XXXXXXXXXX
GOOGLE_TAG_MANAGER_ID=GTM-XXXXXXX
FACEBOOK_PIXEL_ID=

GOOGLE_VERIFICATION=
BING_VERIFICATION=
```

## SEO Best Practices

### Title Tags

✅ **DO:**

- Keep titles between 50-60 characters
- Include primary keyword near the beginning
- Make each page title unique
- Use your brand name consistently

❌ **DON'T:**

- Stuff keywords
- Use ALL CAPS
- Duplicate titles across pages
- Exceed 60 characters (gets truncated in search results)

```php
// Good
$view->setTitle('Complete Guide to SEO - YourBrand');

// Bad
$view->setTitle('SEO SEO SEO BEST SEO GUIDE EVER SEO TIPS SEO TRICKS');
```

### Meta Descriptions

✅ **DO:**

- Keep between 120-160 characters
- Include a call-to-action
- Accurately describe the page content
- Use active voice

❌ **DON'T:**

- Copy content directly from the page
- Use generic descriptions
- Exceed 160 characters
- Include quotation marks (they get truncated)

```php
// Good
$view->setDescription('Learn proven SEO strategies to boost your rankings. Step-by-step guide with actionable tips. Start improving your visibility today!');

// Bad
$view->setDescription('This page is about SEO. SEO is important. We talk about SEO here.');
```

### Keywords

✅ **DO:**

- Use 5-10 relevant keywords
- Include long-tail variations
- Research competition

❌ **DON'T:**

- Keyword stuff
- Use irrelevant keywords
- Repeat the same keyword

```php
// Good
$view->setKeywords(['seo guide', 'search optimization', 'google ranking', 'organic traffic']);

// Bad
$view->setKeywords(['seo', 'seo', 'seo', 'seo tips', 'seo tricks', 'seo guide', 'seo help']);
```

### Images & Open Graph

✅ **DO:**

- Use 1200x630px for OG images
- Optimize image file sizes
- Use descriptive alt text
- Include image dimensions

❌ **DON'T:**

- Use relative URLs
- Change canonical URLs frequently
- Point to different domains without purpose

```php
// Good
$view->setCanonical('https://yourdomain.com/blog/article-name');

// Bad
$view->setCanonical('/blog/article-name'); // Relative URL
```

### Structured Data (Schema.org)

✅ **DO:**

- Use appropriate schema types
- Test with Google's Rich Results Test
- Keep data accurate and updated
- Use multiple schema types when relevant

❌ **DON'T:**

- Add schema for content not on the page
- Use incorrect schema types
- Include misleading information

```php
// Test your structured data:
// https://search.google.com/test/rich-results
```

## Performance Optimization

### Resource Hints

```php
// Preload critical resources (above-the-fold CSS, fonts)
@preload('/css/critical.css', 'style')
@preload('/fonts/main.woff2', 'font')

// DNS prefetch for external domains
@dnsPrefetch('https://fonts.googleapis.com')
@dnsPrefetch('https://www.google-analytics.com')

// Preconnect for critical third-party origins
@preconnect('https://fonts.gstatic.com')

// Prefetch for next likely navigation
@prefetch('/next-page.html')
```

### Image Optimization

- Use WebP format with fallbacks
- Implement lazy loading
- Serve responsive images
- Compress images (80-85% quality)
- Use CDN for images

## Common Patterns

### Blog Posts

```php
public function showBlogPost($slug)
{
    $post = Post::findBySlug($slug);
    
    return View::make('blog.show', compact('post'))
        ->setTitle($post->title)
        ->setDescription($post->excerpt)
        ->setKeywords($post->tags)
        ->setCanonical(url('/blog/' . $slug))
        ->setOgImage($post->featured_image)
        ->setOgType('article')
        ->addArticleSchema([
            'headline' => $post->title,
            'author' => $post->author->name,
            'datePublished' => $post->published_at,
            'dateModified' => $post->updated_at,
            'description' => $post->excerpt,
            'image' => $post->featured_image
        ])
        ->addBreadcrumb([
            ['name' => 'Home', 'url' => '/'],
            ['name' => 'Blog', 'url' => '/blog'],
            ['name' => $post->title, 'url' => '/blog/' . $slug]
        ]);
}
```

### E-commerce Products

```php
public function showProduct($id)
{
    $product = Product::find($id);
    
    return View::make('products.show', compact('product'))
        ->seo([
            'title' => $product->name . ' - Buy Now',
            'description' => $product->short_description,
            'canonical' => url('/products/' . $id),
            'og' => [
                'title' => $product->name,
                'image' => $product->main_image,
                'type' => 'product'
            ]
        ])
        ->addStructuredData([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'image' => $product->images,
            'description' => $product->description,
            'sku' => $product->sku,
            'brand' => ['@type' => 'Brand', 'name' => $product->brand],
            'offers' => [
                '@type' => 'Offer',
                'url' => url('/products/' . $id),
                'priceCurrency' => 'USD',
                'price' => $product->price,
                'availability' => $product->in_stock 
                    ? 'https://schema.org/InStock' 
                    : 'https://schema.org/OutOfStock'
            ]
        ]);
}
```

### Local Business Pages

```php
public function contact()
{
    return View::make('contact')
        ->setTitle('Contact Us - Visit Our Store')
        ->addLocalBusinessSchema([
            'name' => config('app.name'),
            'type' => 'Store',
            'url' => url('/'),
            'telephone' => '+1-555-555-5555',
            'address' => [
                'street' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'zip' => '10001',
                'country' => 'US'
            ],
            'coordinates' => ['lat' => 40.7128, 'lng' => -74.0060],
            'hours' => ['Mo-Fr 09:00-17:00'],
            'priceRange' => '$'
        ]);
}
```

## Testing & Validation

### Tools to Use

1. **Google Search Console**
   - Monitor search performance
   - Check indexing status
   - Submit sitemaps

2. **Google Rich Results Test**
   - <https://search.google.com/test/rich-results>
   - Test structured data

3. **Meta Tags Checker**
   - <https://metatags.io>
   - Preview social shares

4. **PageSpeed Insights**
   - <https://pagespeed.web.dev>
   - Check Core Web Vitals

5. **Lighthouse (Chrome DevTools)**
   - Audit SEO
   - Check accessibility
   - Measure performance

### Debug Mode Validation

```php
// In your view
@seoValidate

// Returns validation results:
// - Issues (critical problems)
// - Warnings (recommendations)
// - Suggestions (enhancements)
// - Score (0-100)

// Access programmatically:
$validation = $view->validateSeo();
if ($validation['score'] < 70) {
    // Log or alert
}
```

## Environment-Specific Settings

### Development Environment

```php
// Automatically set in config/seo.php
'noindex_environments' => ['local', 'development', 'staging']
```

This automatically adds `noindex, nofollow` to prevent indexing of dev sites.

### Production Checklist

- [ ] Remove noindex from production
- [ ] Set proper canonical URLs
- [ ] Configure Google Analytics
- [ ] Submit sitemap to Google Search Console
- [ ] Verify site ownership (Google, Bing)
- [ ] Test all structured data
- [ ] Check mobile responsiveness
- [ ] Optimize images
- [ ] Enable caching
- [ ] Set up redirects for old URLs

## Advanced Techniques

### Dynamic SEO Based on User

```php
View::composer('*', function($view) {
    // Personalize but keep SEO-friendly
    $locale = session('locale', 'en');
    $view->setOgLocale($locale . '_US');
});
```

### A/B Testing Titles

```php
$titleVariant = rand(1, 2);
$title = $titleVariant === 1 
    ? 'Buy Premium Products - 50% Off'
    : 'Premium Products Sale - Save Big';
    
$view->setTitle($title);
```

### Automatic Social Images

```php
// Generate OG images dynamically
$ogImage = ImageService::generateOgImage([
    'title' => $post->title,
    'author' => $post->author->name,
    'template' => 'blog-post'
]);

$view->setOgImage($ogImage);
```

## Common Mistakes to Avoid

1. **Duplicate Content**
   - Always use canonical URLs
   - Consolidate similar pages
   - Use 301 redirects for moved content

2. **Missing Mobile Optimization**
   - Test on real devices
   - Use responsive images
   - Check touch targets

3. **Slow Page Speed**
   - Optimize images
   - Minimize CSS/JS
   - Use caching
   - Enable compression

4. **Broken Links**
   - Monitor 404 errors
   - Set up proper redirects
   - Use absolute URLs

5. **Ignoring Analytics**
   - Review search queries
   - Monitor click-through rates
   - Track conversions
   - Adjust based on data

## Extending the SEO System

### Custom Directives

```php
// In a service provider
$compiler->directive('customSeo', function($expression) {
    return "<?php \$__view->customSeoLogic($expression); ?>";
});
```

### Custom Schema Types

```php
// Add to View class or use macro
View::macro('addVideoSchema', function($video) {
    return $this->addStructuredData([
        '@context' => 'https://schema.org',
        '@type' => 'VideoObject',
        'name' => $video['title'],
        'description' => $video['description'],
        'thumbnailUrl' => $video['thumbnail'],
        'uploadDate' => $video['published_at'],
        'duration' => $video['duration'],
        'contentUrl' => $video['url']
    ]);
});
```

### SEO Middleware

```php
class SeoMiddleware
{
    public function handle($request, $next)
    {
        $response = $next($request);
        
        // Add security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        
        return $response;
    }
}
```

## Monitoring & Maintenance

### Regular Tasks

**Weekly:**

- Check Google Search Console for errors
- Review new search queries
- Monitor rankings for key terms

**Monthly:**

- Update outdated content
- Fix broken links
- Review analytics
- Update schema markup if needed

**Quarterly:**

- Comprehensive SEO audit
- Competitor analysis
- Update keyword strategy
- Review backlink profile

## Resources

- Google SEO Starter Guide: <https://developers.google.com/search/docs/beginner/seo-starter-guide>
- Schema.org Documentation: <https://schema.org/docs/documents.html>
- Google Search Central: <https://developers.google.com/search>
- Open Graph Protocol: <https://ogp.me/>

## Troubleshooting

### Meta Tags Not Showing

```php
// Make sure @metatags is in your layout
@metatags

// Check if SEO provider is registered
var_dump(app('seo')); // Should return SeoHelper instance
```

### Structured Data Errors

```php
// Export and test
$seoData = $view->exportSeoData();
echo json_encode($seoData['structured_data'], JSON_PRETTY_PRINT);

// Test at: https://search.google.com/test/rich-results
```

### Canonical URL Issues

```php
// Verify canonical base URL is set
echo View::getSeoConfig('canonical_base_url');

// Should match APP_URL in .env
```

### Performance Issues

```php
// Enable view caching in production
View::setCaching(true, storage_path('cache/views'));

// Use asset versioning
View::enableAutoVersioning(true);
```

---

## Quick Start Checklist

- [ ] Register `ViewSeoServiceProvider`
- [ ] Create `config/seo.php`
- [ ] Set environment variables in `.env`
- [ ] Add `@metatags` to layout
- [ ] Set page titles in controllers/views
- [ ] Configure Google Analytics
- [ ] Test with validation tools
- [ ] Submit sitemap to search engines

Your SEO system is now fully configured and ready to use! images larger than 2MB

- Forget mobile optimization
- Use generic stock photos

```php
$view->setOgImage('/images/article-featured.jpg', [
    'width' => 1200,
    'height' => 630,
    'alt' => 'Descriptive alt text'
]);
```

### Canonical URLs

✅ **DO:**

- Always set canonical URLs
- Use absolute URLs
- Point to the preferred version
- Be consistent with trailing slashes

❌ **DON'T:**

- Use
