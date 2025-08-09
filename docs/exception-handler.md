# Plugs Framework Exception Handler Documentation

## Table of Contents

1. [Overview](#overview)
2. [Features](#features)
3. [Installation](#installation)
4. [Configuration](#configuration)
5. [Usage](#usage)
6. [UI Components](#ui-components)
7. [Customization](#customization)
8. [API Reference](#api-reference)
9. [Troubleshooting](#troubleshooting)
10. [Best Practices](#best-practices)
11. [Security Considerations](#security-considerations)
12. [Performance](#performance)

## Overview

The Plugs Framework Exception Handler is a sophisticated, professional-grade error handling and debugging tool designed to provide developers with comprehensive exception information in an elegant, user-friendly interface. It transforms PHP exceptions into beautifully formatted, interactive debug pages that significantly enhance the development experience.

### Key Philosophy

- **Developer-First Experience**: Prioritizes clarity and usability for developers
- **Professional Aesthetics**: Modern, clean design that's easy on the eyes
- **Comprehensive Information**: Provides all necessary debugging information in one place
- **Security-Aware**: Environment-specific rendering to prevent information leakage
- **Performance-Optimized**: Minimal overhead with efficient rendering

## Features

### 🎨 **Visual Design**

- **Modern UI**: Contemporary design with gradient accents and professional typography
- **Framework Branding**: Customizable logo and branding elements
- **Responsive Layout**: Optimized for desktop, tablet, and mobile devices
- **Dark Theme**: Eye-friendly dark theme with carefully selected colors
- **Smooth Animations**: Subtle animations and transitions for better UX

### 🛠 **Debugging Capabilities**

- **Stack Trace Analysis**: Interactive, expandable stack trace with syntax highlighting
- **Source Code Context**: Displays code snippets around exception points
- **Request Information**: Comprehensive HTTP request data display
- **Environment Details**: System and PHP configuration information
- **Search Functionality**: Real-time search through stack traces

### 🔧 **Interactive Features**

- **Tabbed Navigation**: Organized content in easy-to-navigate tabs
- **Code Copying**: One-click code snippet copying
- **Collapsible Sections**: Expandable trace items for detailed inspection
- **Keyboard Shortcuts**: Efficient navigation using keyboard shortcuts
- **Search & Filter**: Advanced filtering capabilities

### 🔒 **Security Features**

- **Environment-Aware**: Only shows detailed information in development
- **Production Mode**: Simple error messages in production environments
- **Sanitized Output**: All user data is properly escaped and sanitized
- **Configurable Exposure**: Control what information is displayed

## Installation

### Requirements

- PHP 7.4 or higher
- Plugs Framework (latest version)
- Web server (Apache/Nginx)

### Basic Installation

1. **Add to your Plugs application**:

   ```php
   // In your bootstrap/app.php or service provider
   use Plugs\Exceptions\Handler\ExceptionHandler;
   
   $app->singleton(ExceptionHandler::class, function ($app) {
       return new ExceptionHandler($app);
   });
   ```

2. **Register in your error handling**:

   ```php
   // In your global exception handler
   public function render($request, Throwable $exception)
   {
       $handler = app(ExceptionHandler::class);
       return $handler->render($request, $exception);
   }
   ```

### Advanced Installation

For advanced setups with custom error handling:

```php
// Custom exception handler setup
class CustomExceptionHandler extends ExceptionHandler
{
    protected function shouldShowDebug(): bool
    {
        return $this->app->isEnvironment(['local', 'testing', 'staging']);
    }
    
    protected function getCustomEnvironmentData(): array
    {
        return [
            'Application Version' => config('app.version'),
            'Git Commit' => exec('git rev-parse HEAD'),
            'Deployment Time' => config('app.deployed_at'),
        ];
    }
}
```

## Configuration

### Environment Configuration

The exception handler automatically detects the environment and adjusts its behavior:

```php
// .env configuration
APP_ENV=local          # Shows full debug information
APP_DEBUG=true         # Enables detailed error reporting

// For production
APP_ENV=production     # Shows minimal error information
APP_DEBUG=false        # Disables debug features
```

### Custom Configuration Options

```php
// In your service provider or configuration
$handler->configure([
    'show_code_context' => true,        // Show source code snippets
    'code_context_lines' => 10,         // Lines of context to show
    'enable_search' => true,            // Enable stack trace search
    'show_request_data' => true,        // Show HTTP request information
    'show_environment' => true,         // Show system environment
    'enable_copy_code' => true,         // Enable code copying feature
    'theme' => 'dark',                  // UI theme (dark/light)
    'brand_name' => 'Your Framework',   // Custom framework name
    'brand_tagline' => 'Custom tagline', // Custom tagline
]);
```

## Usage

### Basic Exception Handling

```php
<?php
try {
    // Your application code
    $result = riskyOperation();
} catch (Exception $e) {
    // The exception handler will automatically catch and display
    throw $e; // Re-throw to let the handler catch it
}
```

### Custom Exception Types

```php
<?php
class DatabaseException extends Exception
{
    protected $query;
    protected $bindings;
    
    public function __construct($message, $query = null, $bindings = [])
    {
        parent::__construct($message);
        $this->query = $query;
        $this->bindings = $bindings;
    }
    
    public function getQuery() { return $this->query; }
    public function getBindings() { return $this->bindings; }
}
```

### Logging Integration

```php
<?php
public function report(Throwable $exception): void
{
    // Custom logging logic
    if ($this->shouldReport($exception)) {
        Log::error('Exception occurred', [
            'exception' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'url' => request()->fullUrl(),
            'user' => auth()->id(),
            'ip' => request()->ip(),
        ]);
    }
    
    // Call parent to maintain default behavior
    parent::report($exception);
}
```

## UI Components

### Header Section

The header contains:

- **Framework Branding**: Logo, name, and tagline
- **Environment Badge**: Current environment indicator
- **Exception Summary**: Exception message and basic information
- **File Location**: Source file and line number

### Navigation Sidebar

Sticky sidebar with navigation tabs:

- **Stack Trace**: Interactive stack trace analysis
- **Source Code**: Code context around the exception
- **Request Data**: HTTP request information
- **Environment**: System and PHP configuration

### Content Panels

#### Stack Trace Panel

- **Search Box**: Real-time filtering of stack trace
- **Expandable Items**: Click to view detailed information
- **Code Context**: Syntax-highlighted code snippets
- **Function Arguments**: Detailed parameter information

#### Source Code Panel

- **Syntax Highlighting**: Color-coded PHP syntax
- **Line Numbers**: Numbered lines for easy reference
- **Error Line Highlight**: Visual emphasis on the error line
- **Copy Functionality**: One-click code copying

#### Request Data Panel

- **HTTP Headers**: Complete request headers
- **GET/POST Data**: Form and query parameters
- **Server Information**: Server-specific variables
- **Cookies**: Browser cookies

#### Environment Panel

- **PHP Configuration**: Version, memory limits, timeouts
- **System Information**: OS, architecture, timezone
- **Framework Details**: Environment, debug mode, extensions

### Interactive Features

#### Keyboard Shortcuts

- `Ctrl/Cmd + K`: Focus search box
- `Alt + ←/→`: Navigate between tabs
- `Alt + ↑/↓`: Navigate between tabs (alternative)
- `Esc`: Clear search (when focused)

#### Mouse Interactions

- **Click**: Navigate tabs and expand/collapse sections
- **Hover**: Preview functionality and visual feedback
- **Copy Button**: Click to copy code snippets

## Customization

### Theming

#### Color Customization

```css
:root {
    /* Primary colors */
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --danger-gradient: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
    
    /* Background colors */
    --bg-primary: #0a0e27;
    --bg-secondary: #1a1f3a;
    --bg-card: #2d3451;
    
    /* Text colors */
    --text-primary: #ffffff;
    --text-secondary: #b8c5d1;
    --text-accent: #667eea;
}
```

#### Brand Customization

```php
protected function getBrandConfig(): array
{
    return [
        'name' => 'Your Framework',
        'tagline' => 'Modern PHP Development',
        'logo_text' => 'YF', // Logo abbreviation
        'logo_color' => '#667eea',
    ];
}
```

### Layout Customization

#### Custom CSS Injection

```php
protected function getCustomStyles(): string
{
    return '
        <style>
            .custom-header {
                background: linear-gradient(45deg, #your-colors);
            }
            .custom-brand {
                font-family: "Your Custom Font";
            }
        </style>
    ';
}
```

#### Template Overrides

```php
protected function renderCustomTemplate(Throwable $exception, Request $request): string
{
    // Your custom template logic
    return view('errors.custom-debug', compact('exception', 'request'))->render();
}
```

### Functionality Extension

#### Custom Data Sections

```php
protected function getCustomData(Request $request): array
{
    return [
        'application_data' => [
            'Version' => config('app.version'),
            'Environment' => app()->environment(),
            'Debug Mode' => config('app.debug') ? 'On' : 'Off',
        ],
        'database_info' => [
            'Default Connection' => config('database.default'),
            'Query Log Count' => count(DB::getQueryLog()),
        ],
        'cache_info' => [
            'Default Driver' => config('cache.default'),
            'Cache Hit Rate' => $this->getCacheHitRate(),
        ],
    ];
}
```

#### Custom Exception Processing

```php
protected function processException(Throwable $exception): array
{
    $data = parent::processException($exception);
    
    // Add custom processing
    if ($exception instanceof DatabaseException) {
        $data['database'] = [
            'query' => $exception->getQuery(),
            'bindings' => $exception->getBindings(),
        ];
    }
    
    return $data;
}
```

## API Reference

### ExceptionHandler Class

#### Constructor

```php
public function __construct(Plugs $app)
```

- **Parameters**: `$app` - The Plugs application instance
- **Description**: Initializes the exception handler with the application instance

#### Public Methods

##### render()

```php
public function render(Request $request, Throwable $exception): Response
```

- **Parameters**:
  - `$request` - HTTP request instance
  - `$exception` - The thrown exception
- **Returns**: HTTP response with formatted exception page
- **Description**: Main method that renders the exception into an HTTP response

##### report()

```php
public function report(Throwable $exception): void
```

- **Parameters**: `$exception` - The exception to report
- **Description**: Reports the exception to logging services

#### Protected Methods

##### renderDebugResponse()

```php
protected function renderDebugResponse(Throwable $exception, Request $request): Response
```

- **Description**: Renders the full debug response with detailed information

##### renderProductionResponse()

```php
protected function renderProductionResponse(Throwable $exception): Response
```

- **Description**: Renders a minimal error response for production environments

##### getCodeSnippet()

```php
protected function getCodeSnippet(string $file, int $line, int $padding = 5): string
```

- **Parameters**:
  - `$file` - Path to the source file
  - `$line` - Line number where exception occurred
  - `$padding` - Number of lines to show around the error line
- **Returns**: HTML formatted code snippet
- **Description**: Extracts and formats source code context

##### getRequestData()

```php
protected function getRequestData(Request $request): array
```

- **Parameters**: `$request` - HTTP request instance
- **Returns**: Array of request data organized by type
- **Description**: Extracts and organizes HTTP request information

##### getEnvironmentData()

```php
protected function getEnvironmentData(): array
```

- **Returns**: Array of system and environment information
- **Description**: Collects system, PHP, and application environment data

##### formatBytes()

```php
protected function formatBytes(int $size): string
```

- **Parameters**: `$size` - Size in bytes
- **Returns**: Human-readable size string
- **Description**: Converts bytes to human-readable format (KB, MB, GB, etc.)

##### getErrorReportingLevel()

```php
protected function getErrorReportingLevel(): string
```

- **Returns**: Human-readable error reporting level
- **Description**: Converts PHP error reporting level to readable format

## Troubleshooting

### Common Issues

#### 1. "File not readable" Error

**Problem**: Code snippets show "File not readable" message
**Solution**:

```php
// Check file permissions
chmod 644 /path/to/your/files

// Verify file paths in your configuration
// Ensure the application has read access to source files
```

#### 2. Styles Not Loading

**Problem**: Exception page appears without styling
**Solution**:

```php
// Check Content Security Policy headers
// Ensure cdnjs.cloudflare.com is allowed for external resources

// Verify internet connection for external font/icon loading
```

#### 3. JavaScript Features Not Working

**Problem**: Interactive features (search, expand/collapse) not working
**Solution**:

```html
<!-- Ensure JavaScript is enabled in browser -->
<!-- Check for JavaScript console errors -->
<!-- Verify CSP allows inline scripts -->
```

#### 4. Memory Issues with Large Stack Traces

**Problem**: Page fails to load with very large stack traces
**Solution**:

```php
// Increase memory limit
ini_set('memory_limit', '256M');

// Or limit stack trace depth
protected function getCodeSnippet($file, $line, $padding = 3): string
{
    // Reduce padding to show fewer lines
}
```

### Debug Mode Issues

#### Exception Handler Not Showing

```php
// Verify environment configuration
if (!$this->app->isEnvironment('local', 'testing')) {
    // Handler will show production page
}

// Check debug configuration
if (!config('app.debug')) {
    // Debug features will be disabled
}
```

#### Infinite Exception Loops

```php
// Add safeguards in custom handlers
private static $handling = false;

public function render(Request $request, Throwable $exception): Response
{
    if (self::$handling) {
        return new Response('Exception in exception handler', 500);
    }
    
    self::$handling = true;
    try {
        return parent::render($request, $exception);
    } finally {
        self::$handling = false;
    }
}
```

## Best Practices

### Development Environment

#### 1. Error Reporting Configuration

```php
// In development environment
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// In .env
APP_ENV=local
APP_DEBUG=true
LOG_LEVEL=debug
```

#### 2. Exception Handling Strategy

```php
// Use specific exception types
throw new ValidationException('Invalid input data');
throw new DatabaseException('Query failed', $query, $bindings);

// Add context to exceptions
throw new Exception("Failed to process order #{$orderId}", 0, $previousException);
```

#### 3. Logging Best Practices

```php
public function report(Throwable $exception): void
{
    // Log with appropriate levels
    if ($exception instanceof ValidationException) {
        Log::warning('Validation failed', ['exception' => $exception]);
    } else {
        Log::error('Unexpected exception', ['exception' => $exception]);
    }
    
    // Include relevant context
    Log::error('Exception occurred', [
        'exception' => $exception->getMessage(),
        'user_id' => auth()->id(),
        'request_id' => request()->header('X-Request-ID'),
        'session_id' => session()->getId(),
    ]);
}
```

### Production Environment

#### 1. Security Configuration

```php
// Never show debug information in production
APP_ENV=production
APP_DEBUG=false

// Use generic error messages
protected function renderProductionResponse(Throwable $exception): Response
{
    return new Response('Something went wrong. Please try again later.', 500);
}
```

#### 2. Error Monitoring

```php
// Integrate with monitoring services
public function report(Throwable $exception): void
{
    if ($this->shouldReport($exception)) {
        // Send to external monitoring
        app('sentry')->captureException($exception);
        
        // Send critical alerts
        if ($this->isCritical($exception)) {
            Mail::to('admin@yourapp.com')->send(new CriticalErrorAlert($exception));
        }
    }
}
```

### Performance Optimization

#### 1. Efficient Code Context

```php
// Limit code context in production-like environments
protected function getCodeSnippet(string $file, int $line, int $padding = 5): string
{
    // Reduce padding for better performance
    if ($this->app->isEnvironment('staging')) {
        $padding = 3;
    }
    
    return parent::getCodeSnippet($file, $line, $padding);
}
```

#### 2. Request Data Filtering

```php
protected function getRequestData(Request $request): array
{
    $data = parent::getRequestData($request);
    
    // Remove sensitive information
    unset($data['server_info']['DB_PASSWORD']);
    unset($data['cookies']['session_token']);
    
    // Limit data size
    array_walk_recursive($data, function (&$value) {
        if (is_string($value) && strlen($value) > 1000) {
            $value = substr($value, 0, 1000) . '... (truncated)';
        }
    });
    
    return $data;
}
```

## Security Considerations

### Information Disclosure Prevention

#### 1. Environment-Specific Rendering

```php
public function render(Request $request, Throwable $exception): Response
{
    // Only show detailed information in safe environments
    if (!$this->shouldShowDebugInfo()) {
        return $this->renderProductionResponse($exception);
    }
    
    return $this->renderDebugResponse($exception, $request);
}

protected function shouldShowDebugInfo(): bool
{
    return $this->app->isEnvironment(['local', 'testing']) && 
           config('app.debug') && 
           !$this->isPubliclyAccessible();
}
```

#### 2. Data Sanitization

```php
protected function sanitizeData(array $data): array
{
    $sensitiveKeys = ['password', 'secret', 'token', 'key', 'auth'];
    
    array_walk_recursive($data, function (&$value, $key) use ($sensitiveKeys) {
        if (is_string($key) && $this->isSensitiveKey($key, $sensitiveKeys)) {
            $value = '[REDACTED]';
        }
    });
    
    return $data;
}

protected function isSensitiveKey(string $key, array $sensitiveKeys): bool
{
    foreach ($sensitiveKeys as $sensitive) {
        if (stripos($key, $sensitive) !== false) {
            return true;
        }
    }
    return false;
}
```

### Access Control

#### 1. IP-Based Restrictions

```php
protected function shouldShowDebugInfo(): bool
{
    $allowedIPs = config('debug.allowed_ips', ['127.0.0.1', '::1']);
    $clientIP = request()->ip();
    
    return parent::shouldShowDebugInfo() && 
           in_array($clientIP, $allowedIPs);
}
```

#### 2. Authentication-Based Access

```php
protected function shouldShowDebugInfo(): bool
{
    return parent::shouldShowDebugInfo() && 
           auth()->check() && 
           auth()->user()->hasRole('developer');
}
```

## Performance

### Optimization Strategies

#### 1. Lazy Loading

```php
protected function renderDebugResponse(Throwable $exception, Request $request): Response
{
    // Only generate expensive data when actually displayed
    $lazyData = [
        'code_snippet' => fn() => $this->getCodeSnippet($exception->getFile(), $exception->getLine()),
        'environment' => fn() => $this->getEnvironmentData(),
        'request_data' => fn() => $this->getRequestData($request),
    ];
    
    return $this->renderWithLazyData($exception, $lazyData);
}
```

#### 2. Caching

```php
protected function getCodeSnippet(string $file, int $line, int $padding = 5): string
{
    $cacheKey = "code_snippet:" . md5($file . $line . $padding);
    
    return cache()->remember($cacheKey, 300, function () use ($file, $line, $padding) {
        return parent::getCodeSnippet($file, $line, $padding);
    });
}
```

#### 3. Memory Management

```php
protected function getEnvironmentData(): array
{
    // Monitor memory usage
    $startMemory = memory_get_usage();
    $data = parent::getEnvironmentData();
    $endMemory = memory_get_usage();
    
    $data['Debug Info'] = [
        'Memory used for debug data' => $this->formatBytes($endMemory - $startMemory),
    ];
    
    return $data;
}
```

### Monitoring Performance

```php
protected function renderDebugResponse(Throwable $exception, Request $request): Response
{
    $startTime = microtime(true);
    
    $response = parent::renderDebugResponse($exception, $request);
    
    $endTime = microtime(true);
    $renderTime = round(($endTime - $startTime) * 1000, 2);
    
    // Log slow render times
    if ($renderTime > 1000) { // More than 1 second
        Log::warning("Slow exception page render: {$renderTime}ms");
    }
    
    return $response;
}
```

---

## Contributing

If you'd like to contribute to the Exception Handler:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

### Development Setup

```bash
git clone https://github.com/your-org/plugs-framework
cd plugs-framework
composer install
npm install && npm run dev
```

### Testing

```bash
# Run unit tests
phpunit tests/Unit/ExceptionHandlerTest.php

# Run integration tests
phpunit tests/Feature/ExceptionHandlerTest.php

# Run browser tests
php artisan dusk tests/Browser/ExceptionPageTest.php
```

## License

The Plugs Framework Exception Handler is open-sourced software licensed under the MIT license.

## Support

- **Documentation**: [https://docs.plugs-framework.com](https://docs.plugs-framework.com)
- **Issues**: [GitHub Issues](https://github.com/your-org/plugs-framework/issues)
- **Discussions**: [GitHub Discussions](https://github.com/your-org/plugs-framework/discussions)
- **Discord**: [Community Discord](https://discord.gg/plugs-framework)

---

### Last updated: $(date '+%Y-%m-%d')
