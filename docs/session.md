# Session Management System Documentation

## Table of Contents

1. [Introduction](#introduction)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Basic Usage](#basic-usage)
5. [Session Drivers](#session-drivers)
6. [Security Features](#security-features)
7. [Flash Messages](#flash-messages)
8. [CSRF Protection](#csrf-protection)
9. [Middleware](#middleware)
10. [Helper Functions](#helper-functions)
11. [Advanced Usage](#advanced-usage)
12. [Testing](#testing)
13. [API Reference](#api-reference)
14. [Troubleshooting](#troubleshooting)

---

## Introduction

The Session Management System is a secure, extensible, and production-ready session handling library for PHP 8.1+ frameworks. It provides Laravel-like session functionality with multiple storage drivers, advanced security features, and seamless framework integration.

### Key Features

- **Multiple Storage Drivers**: File, Database, Array (testing), and extensible architecture
- **Advanced Security**: CSRF protection, session regeneration, IP/User-Agent binding
- **Flash Messages**: Temporary data storage for UI notifications
- **Encryption Support**: Optional session data encryption at rest
- **Framework Integration**: Service provider, middleware, and dependency injection support
- **Production Ready**: Comprehensive error handling, garbage collection, and performance optimized

---

## Installation

### 1. Add Session Classes

Copy the session management classes to your framework:

src/Framework/Session/
├── SessionInterface.php
├── SessionManager.php
├── SessionDriverInterface.php
├── FileSessionDriver.php
├── DatabaseSessionDriver.php
├── ArraySessionDriver.php
├── SessionEncryptor.php
└── StartSessionMiddleware.php

### 2. Register Service Provider

Add the `SessionServiceProvider` to your application:

```php
// In your application bootstrap
$app->register(new \Plugs\Services\Providers\SessionServiceProvider($app));
```

### 3. Update Request Class

Add session methods to your Request class as shown in the integration guide.

---

## Configuration

### Creating Configuration File

Create `config/session.php`:

```php
<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Default Session Driver
    |--------------------------------------------------------------------------
    | Supported: "file", "database", "array"
    */
    'driver' => env('SESSION_DRIVER', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Session Lifetime (minutes)
    |--------------------------------------------------------------------------
    */
    'lifetime' => (int) env('SESSION_LIFETIME', 120),
    'expire_on_close' => false,

    /*
    |--------------------------------------------------------------------------
    | Session Encryption
    |--------------------------------------------------------------------------
    */
    'encrypt' => env('SESSION_ENCRYPT', false),
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | File Driver Configuration
    |--------------------------------------------------------------------------
    */
    'path' => storage_path('framework/sessions'),

    /*
    |--------------------------------------------------------------------------
    | Database Driver Configuration
    |--------------------------------------------------------------------------
    */
    'host' => env('DB_HOST', '127.0.0.1'),
    'database' => env('DB_DATABASE'),
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
    'table' => 'sessions',

    /*
    |--------------------------------------------------------------------------
    | Cookie Configuration
    |--------------------------------------------------------------------------
    */
    'cookie' => env('SESSION_COOKIE', 'plugs_session'),
    'path' => '/',
    'domain' => env('SESSION_DOMAIN'),
    'secure' => env('SESSION_SECURE_COOKIE', false),
    'http_only' => true,
    'same_site' => 'lax',

    /*
    |--------------------------------------------------------------------------
    | Security Options
    |--------------------------------------------------------------------------
    */
    'check_ip' => false,
    'check_user_agent' => false,
    
    /*
    |--------------------------------------------------------------------------
    | Garbage Collection
    |--------------------------------------------------------------------------
    */
    'gc_probability' => 1,
    'gc_divisor' => 100,
];
```

### Environment Variables

Add these to your `.env` file:

```env
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_COOKIE=plugs_session
SESSION_SECURE_COOKIE=false
SESSION_DOMAIN=
APP_KEY=your-32-character-secret-key
```

---

## Basic Usage

### In Controllers

#### Using Dependency Injection

```php
<?php
namespace App\Controllers;

use Framework\Session\SessionInterface;
use Plugs\Http\Request\Request;

class UserController 
{
    public function __construct(
        private SessionInterface $session
    ) {}
    
    public function dashboard()
    {
        // Store data
        $this->session->put('user.name', 'John Doe');
        $this->session->put('user.email', 'john@example.com');
        
        // Retrieve data
        $userName = $this->session->get('user.name', 'Guest');
        
        // Check existence
        if ($this->session->has('user.id')) {
            // User is logged in
        }
        
        return view('dashboard', compact('userName'));
    }
}
```

#### Using Request Object

```php
public function profile(Request $request)
{
    $session = $request->session();
    
    // Or use request helper methods
    $request->sessionPut('last_page', '/profile');
    $lastPage = $request->sessionGet('last_page');
}
```

#### Using Global Helper

```php
public function settings()
{
    // Store data
    session(['theme' => 'dark', 'language' => 'en']);
    
    // Retrieve data
    $theme = session('theme', 'light');
    
    // Get session instance
    $sessionInstance = session();
}
```

---

## Session Drivers

### File Driver (Default)

Stores session data in files on the server filesystem.

```php
'driver' => 'file',
'path' => storage_path('framework/sessions'),
```

**Pros:**

- Simple setup, no database required
- Good performance for moderate traffic
- Built-in file locking

**Cons:**

- Not suitable for load-balanced environments
- Requires shared filesystem for multiple servers

### Database Driver

Stores session data in a database table.

```php
'driver' => 'database',
'host' => env('DB_HOST'),
'database' => env('DB_DATABASE'),
'username' => env('DB_USERNAME'),
'password' => env('DB_PASSWORD'),
'table' => 'sessions',
```

The sessions table is automatically created with this structure:

```sql
CREATE TABLE sessions (
    id VARCHAR(40) PRIMARY KEY,
    payload LONGTEXT NOT NULL,
    last_activity INT NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    INDEX sessions_last_activity_index (last_activity)
);
```

**Pros:**

- Scalable across multiple servers
- Can store additional metadata
- ACID compliance

**Cons:**

- Requires database connection
- Slightly higher latency than file storage

### Array Driver

Stores session data in memory (for testing only).

```php
'driver' => 'array',
```

**Use Cases:**

- Unit testing
- Development environments
- Temporary storage scenarios

---

## Security Features

### CSRF Protection

Every session automatically generates a CSRF token:

```php
// In controller
$token = $this->session->token();

// In view template
<form method="POST">
    {{ csrf_field() }}
    <!-- form fields -->
</form>

// Manual token validation
if (!$request->hasValidCsrfToken()) {
    throw new Exception('CSRF token mismatch');
}
```

### Session Regeneration

Regenerate session ID to prevent fixation attacks:

```php
// After successful login
$this->session->regenerate();

// Complete session invalidation
$this->session->invalidate();
```

### IP and User-Agent Binding

Prevent session hijacking by binding sessions to client characteristics:

```php
'check_ip' => true,           // Bind to IP address
'check_user_agent' => true,   // Bind to User-Agent string
```

### Session Encryption

Encrypt session data at rest:

```php
'encrypt' => true,
'key' => env('APP_KEY'),      // 32-character secret key
'cipher' => 'AES-256-CBC',
```

### Secure Cookies

Configure secure cookie settings:

```php
'secure' => true,        // HTTPS only
'http_only' => true,     // No JavaScript access
'same_site' => 'strict', // CSRF protections
```

---

## Flash Messages

Flash messages are temporary data stored for the next request only.

### Storing Flash Data

```php
// Basic flash message
$this->session->flash('success', 'Profile updated successfully!');

// Multiple flash messages
$this->session->flash('error', 'Invalid email address');
$this->session->flash('warning', 'Password will expire soon');

// Flash with redirect (in controller)
return redirect('/dashboard')->with('success', 'Welcome back!');
```

### Retrieving Flash Data

```php
// In controller
$message = $this->session->get('success');

// In view template
@if($session->has('success'))
    <div class="alert alert-success">
        {{ $session->get('success') }}
    </div>
@endif

// Using helper function
{{ flash_message('success') }}
```

### Reflashing Data

Keep flash data for another request:

```php
// Keep all flash data
$this->session->reflash();

// Keep specific keys (if implemented)
$this->session->keep(['success', 'error']);
```

### Auto-Expiration

Flash data is automatically removed after being displayed once.

## CSRF Protections

### How It Works

1. A unique token is generated for each session
2. The token must be included in state-changing requests
3. The token is validated before processing the request

### Implementation

#### In Forms

```html
<form method="POST" action="/login">
    {{ csrf_field() }}
    <input type="email" name="email" required>
    <input type="password" name="password" required>
    <button type="submit">Login</button>
</form>
```

#### In AJAX Requests

```javascript
// Add to request headers
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
    }
});

// Or in form data
fetch('/api/endpoint', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
    body: JSON.stringify(data)
});
```

#### Manual Validation

```php
public function updateProfile(Request $request)
{
    if (!$request->hasValidCsrfToken()) {
        return response('CSRF token mismatch', 419);
    }
    
    // Process request...
}
```

---

## Middleware

### StartSessionMiddleware

Automatically starts sessions and injects them into requests.

#### Registration

```php
// Global middleware (recommended)
protected $middleware = [
    \Framework\Session\StartSessionMiddleware::class,
];

// Route group middleware
protected $middlewareGroups = [
    'web' => [
        \Framework\Session\StartSessionMiddleware::class,
    ],
];
```

#### Custom Middleware

Create additional session-related middleware:

```php
<?php
namespace App\Http\Middleware;

class AuthMiddleware
{
    public function handle($request, \Closure $next)
    {
        if (!$request->session()->has('user_id')) {
            return redirect('/login');
        }
        
        return $next($request);
    }
}

class CsrfMiddleware
{
    public function handle($request, \Closure $next)
    {
        if (in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            if (!$request->hasValidCsrfToken()) {
                abort(419, 'CSRF token mismatch');
            }
        }
        
        return $next($request);
    }
}
```

---

## Helper Functions

### Global Session Helper

```php
// Get session instance
$session = session();

// Get value with default
$value = session('key', 'default');

// Set multiple values
session(['key1' => 'value1', 'key2' => 'value2']);

// Check if key exists
if (session()->has('user_id')) {
    // User is logged in
}
```

### CSRF Helpers

```php
// Get CSRF token
$token = csrf_token();

// Generate hidden input field
echo csrf_field(); // <input type="hidden" name="_token" value="...">
```

### Authentication Helpers

```php
// Check if user is authenticated
if (is_authenticated($request)) {
    // User is logged in
}

// Get current user data
$user = current_user($request);
echo "Welcome, {$user['name']}!";
```

### Flash Message Helper

```php
// Get flash message
$message = flash_message($request, 'success');

// Display flash messages in view
@if($message = flash_message($request, 'success'))
    <div class="alert alert-success">{{ $message }}</div>
@endif
```

---

## Advanced Usage

### Custom Session Driver

Create your own session driver by implementing `SessionDriverInterface`:

```php
<?php
namespace App\Session;

use Framework\Session\SessionDriverInterface;

class RedisSessionDriver implements SessionDriverInterface
{
    private $redis;
    
    public function __construct(array $config)
    {
        $this->redis = new \Redis();
        $this->redis->connect($config['host'], $config['port']);
    }
    
    public function read(string $sessionId): string
    {
        return $this->redis->get("session:$sessionId") ?: '';
    }
    
    public function write(string $sessionId, string $data): bool
    {
        return $this->redis->setex("session:$sessionId", 3600, $data);
    }
    
    public function destroy(string $sessionId): bool
    {
        return $this->redis->del("session:$sessionId") > 0;
    }
    
    public function gc(int $maxLifetime): bool
    {
        // Redis handles TTL automatically
        return true;
    }
    
    public function exists(string $sessionId): bool
    {
        return $this->redis->exists("session:$sessionId");
    }
}
```

Register the custom driver:

```php
// In SessionServiceProvider
$this->app->bind('session.driver.redis', function (Container $app) {
    $config = $app->get('session.config');
    return new RedisSessionDriver($config);
});
```

### Session Events

Implement session events for logging and monitoring:

```php
<?php
namespace App\Session;

class SessionEventHandler
{
    public function onSessionStart(string $sessionId): void
    {
        Log::info("Session started: $sessionId");
    }
    
    public function onSessionRegenerate(string $oldId, string $newId): void
    {
        Log::info("Session regenerated: $oldId -> $newId");
    }
    
    public function onSessionDestroy(string $sessionId): void
    {
        Log::info("Session destroyed: $sessionId");
    }
}
```

### Custom Encryption

Use a custom encryption implementation:

```php
<?php
namespace App\Session;

class CustomSessionEncryptor
{
    public function encrypt(string $data): string
    {
        // Your encryption logic
        return sodium_crypto_secretbox($data, $nonce, $key);
    }
    
    public function decrypt(string $data): string
    {
        // Your decryption logic
        return sodium_crypto_secretbox_open($data, $nonce, $key);
    }
}
```

---

## Testing

### Unit Testing

```php
<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Framework\Session\SessionManager;
use Framework\Session\ArraySessionDriver;

class SessionTest extends TestCase
{
    private SessionManager $session;
    
    protected function setUp(): void
    {
        ArraySessionDriver::clear();
        
        $config = [
            'driver' => 'array',
            'lifetime' => 120,
            'cookie' => 'test_session',
        ];
        
        $this->session = new SessionManager($config);
        $this->session->start();
    }
    
    public function testPutAndGet(): void
    {
        $this->session->put('test_key', 'test_value');
        $this->assertEquals('test_value', $this->session->get('test_key'));
    }
    
    public function testFlashData(): void
    {
        $this->session->flash('message', 'Flash message');
        $this->assertEquals('Flash message', $this->session->get('message'));
        
        // Simulate new request
        $this->session->start();
        // Flash data should be removed after first access
    }
    
    public function testCsrfToken(): void
    {
        $token1 = $this->session->token();
        $token2 = $this->session->token();
        
        $this->assertEquals($token1, $token2);
        $this->assertEquals(64, strlen($token1)); // 32 bytes = 64 hex chars
    }
}
```

### Integration Testing

```php
<?php
namespace Tests\Integration;

use Tests\TestCase;

class SessionIntegrationTest extends TestCase
{
    public function testSessionMiddleware(): void
    {
        $response = $this->get('/dashboard');
        
        // Check that session was started
        $this->assertTrue($this->app->has('session'));
        
        // Check session data persistence
        $this->post('/login', ['email' => 'test@example.com']);
        $this->get('/profile');
        
        $session = $this->app->get('session');
        $this->assertTrue($session->has('user_id'));
    }
    
    public function testCsrfProtection(): void
    {
        // Request without CSRF token should fail
        $response = $this->post('/update-profile', [
            'name' => 'John Doe'
        ]);
        
        $response->assertStatus(419); // CSRF token mismatch
        
        // Request with valid CSRF token should succeed
        $token = csrf_token();
        $response = $this->post('/update-profile', [
            'name' => 'John Doe',
            '_token' => $token
        ]);
        
        $response->assertStatus(200);
    }
}
```

---

## API Reference

### SessionInterface

#### Basic Methods

```php
public function start(): bool;
public function put(string $key, mixed $value): void;
public function get(string $key, mixed $default = null): mixed;
public function has(string $key): bool;
public function forget(string|array $keys): void;
public function all(): array;
public function clear(): void;
```

#### Flash Methods

```php
public function flash(string $key, mixed $value): void;
public function reflash(): void;
```

#### Security Methods

```php
public function regenerate(): bool;
public function invalidate(): bool;
public function token(): string;
public function getId(): ?string;
```

### Request Session Methods

```php
public function setSession(SessionInterface $session): void;
public function session(): ?SessionInterface;
public function hasSession(): bool;
public function sessionGet(string $key, mixed $default = null): mixed;
public function sessionPut(string $key, mixed $value): void;
public function sessionFlash(string $key, mixed $value): void;
public function csrfToken(): ?string;
public function hasValidCsrfToken(): bool;
```

### SessionDriverInterface

```php
public function read(string $sessionId): string;
public function write(string $sessionId, string $data): bool;
public function destroy(string $sessionId): bool;
public function gc(int $maxLifetime): bool;
public function exists(string $sessionId): bool;
```

---

## Troubleshooting

### Common Issues

#### Session Not Starting

**Problem**: Sessions are not being created or data is not persisting.

**Solutions**:

1. Ensure `StartSessionMiddleware` is registered
2. Check file permissions for session storage directory
3. Verify PHP session configuration
4. Check that `session_start()` is not called elsewhere

```php
// Debug session status
echo 'Session Status: ' . session_status() . PHP_EOL;
echo 'Session ID: ' . session_id() . PHP_EOL;
echo 'Session Data: ' . print_r($_SESSION, true) . PHP_EOL;
```

#### CSRF Token Mismatch

**Problem**: Forms are failing with CSRF token errors.

**Solutions**:

1. Ensure `{{ csrf_field() }}` is included in forms
2. Check that session is started before generating tokens
3. Verify token is being sent in request headers for AJAX
4. Check for session regeneration issues

```php
// Debug CSRF token
$sessionToken = $request->session()->token();
$requestToken = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');
echo "Session Token: $sessionToken" . PHP_EOL;
echo "Request Token: $requestToken" . PHP_EOL;
echo "Tokens Match: " . ($sessionToken === $requestToken ? 'Yes' : 'No') . PHP_EOL;
```

#### Database Driver Issues

**Problem**: Database session driver connection failures.

**Solutions**:

1. Verify database credentials in configuration
2. Ensure sessions table exists (it's auto-created)
3. Check database user permissions
4. Verify PDO extension is installed

```php
// Test database connection
try {
    $pdo = new PDO($dsn, $username, $password);
    echo "Database connection successful" . PHP_EOL;
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . PHP_EOL;
}
```

#### File Driver Permission Issues

**Problem**: Cannot write session files.

**Solutions**:

1. Check directory permissions (755 or 777)
2. Ensure web server can write to session directory
3. Verify disk space availability
4. Check SELinux/AppArmor policies

```bash
# Fix permissions
chmod 755 storage/framework/sessions
chown -R www-data:www-data storage/framework/sessions

# Check disk space
df -h
```

#### Session Data Not Encrypting

**Problem**: Session encryption is enabled but data appears unencrypted.

**Solutions**:

1. Verify `APP_KEY` is set and 32 characters long
2. Check OpenSSL extension is installed
3. Ensure encryption configuration is correct
4. Test encryption manually

```php
// Test encryption
$encryptor = new SessionEncryptor(env('APP_KEY'));
$encrypted = $encryptor->encrypt('test data');
$decrypted = $encryptor->decrypt($encrypted);
echo "Original: test data" . PHP_EOL;
echo "Encrypted: $encrypted" . PHP_EOL;
echo "Decrypted: $decrypted" . PHP_EOL;
```

### Debug Mode

Enable debug logging for session operations:

```php
// In SessionManager constructor
if (app()->environment('local')) {
    ini_set('log_errors', 1);
    ini_set('error_log', storage_path('logs/session.log'));
}
```

### Performance Optimization

1. **Use appropriate driver**: File for single server, Database for multiple servers
2. **Configure garbage collection**: Balance frequency vs performance
3. **Optimize session data**: Store only necessary data
4. **Use session regeneration sparingly**: Only on authentication events
5. **Consider Redis/Memcached**: For high-traffic applications

```php
// Optimize garbage collection
'gc_probability' => 1,
'gc_divisor' => 1000,  // Run GC on 0.1% of requests

// Minimize session data
$session->put('user_id', $userId);  // Good
$session->put('user', $fullUserObject);  // Avoid large objects
```

---

## Conclusion

The Session Management System provides a robust, secure, and flexible foundation for handling user sessions in your PHP application. With its multiple storage drivers, comprehensive security features, and seamless framework integration, it's ready for production use while remaining easy to extend and customize.

For additional support or feature requests, please refer to your framework's documentation or submit an issue to the project repository.
