<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Public Index File (For Distribution)
|--------------------------------------------------------------------------
|
| This is the entry point to your application. This file detects whether
| the application has been installed and redirects to the installer if needed.
*/

/*
 |----------------------------------------------------------------------
 | Check Installation Status
 |----------------------------------------------------------------------
 |
 | If the plugs.lock marker file doesn't exist and the install directory
 | exists, redirect to the installation wizard.
 */
/*
 |----------------------------------------------------------------------
 | Check Installation Status
 |----------------------------------------------------------------------
 |
 | If the plugs.lock marker file doesn't exist and the install directory
 | exists, redirect to the installation wizard.
 */
if (!file_exists(__DIR__ . '/../plugs.lock') && is_dir(__DIR__ . '/install')) {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';

    // If we are not already trying to access the installer
    if (strpos($requestUri, '/install') === false) {
        header('Location: install/');
        exit;
    }

    // If we are here, the user is visiting /install but the web server
    // is routing it to public/index.php (Loop detected / Directory not accessible)
    http_response_code(503);
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Setup Required - Plugs Framework</title>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
        <style>
            body { font-family: "Outfit", sans-serif; background: #080b12; color: #fff; display: flex; height: 100vh; align-items: center; justify-content: center; margin: 0; }
            .container { text-align: center; max-width: 500px; padding: 2rem; background: rgba(30,41,59,0.5); border-radius: 16px; border: 1px solid rgba(255,255,255,0.1); }
            h1 { color: #8b5cf6; margin-bottom: 1rem; }
            p { color: #94a3b8; line-height: 1.6; margin-bottom: 2rem; }
            .btn { display: inline-block; padding: 0.75rem 1.5rem; background: #8b5cf6; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: all 0.2s; }
            .btn:hover { background: #7c3aed; transform: translateY(-2px); }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>Setup Required</h1>
            <p>The application is not installed. Please access the installer to set up your framework.</p>
            <a href="install/" class="btn">Launch Installer</a>
        </div>
    </body>
    </html>';
    exit;
}

/*
 |----------------------------------------------------------------------
 | Load the Bootstrap File
 |----------------------------------------------------------------------
 |
 | Here we are loading the bootstrap file to set up the application.
 */
$app = require __DIR__ . '/../bootstrap/boot.php';

/*
 |----------------------------------------------------------------------
 | Run the Application
 |----------------------------------------------------------------------
 |
 | Now we can run the application. This is where the main logic of
 | your application will be executed.
 */

try {
    $app->run();
} catch (\Throwable $e) {
    // 1. Determine Environment & Debug State
    $isLocal = ($_ENV['APP_ENV'] ?? 'production') === 'local';
    $isDebug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);

    // 2. Ensure display_errors matches debug state (Security)
    ini_set('display_errors', $isDebug ? '1' : '0');

    // 3. Ensure error functions are loaded (Now handled by Composer autoloader)
    // We check if function exists just in case the autoloader failed or wasn't loaded yet.
    if (!function_exists('renderDebugErrorPage')) {
        // Fallback for development if vendor/autoload.php didn't include it
        $devPath = __DIR__ . '/../src/functions/error.php';
        if (file_exists($devPath)) {
            require_once $devPath;
        }
    }

    // 4. Try to use the central exception handler if application is partially booted
    try {
        $container = \Plugs\Container\Container::getInstance();
        if ($container->has(\Plugs\Exceptions\Handler::class)) {
            $handler = $container->make(\Plugs\Exceptions\Handler::class);
            $request = \Plugs\Http\Message\ServerRequest::fromGlobals();
            $response = $handler->handle($e, $request);

            // If we got a response, emit it (manually if needed)
            $body = (string) $response->getBody();
            http_response_code($response->getStatusCode());
            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
            echo $body;
            exit;
        }
    } catch (\Throwable $handlerError) {
        // Fallback to manual rendering if handler fails
    }

    // 5. Try to Render Debug Page directly
    if ($isDebug && function_exists('renderDebugErrorPage')) {
        renderDebugErrorPage($e);
        exit;
    }

    // 6. Try to Render Production Page directly
    if (function_exists('renderProductionErrorPage')) {
        renderProductionErrorPage($e, 500);
        exit;
    }

    // 5. Emergency Fallback (If framework functions fail)
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Server Error</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <style>
            @import url("https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Dancing+Script:wght@700&display=swap");

            :root {
                --bg-body: #080b12;
                --bg-card: rgba(30, 41, 59, 0.5);
                --text-primary: #f8fafc;
                --text-secondary: #94a3b8;
                --accent-primary: #8b5cf6;
                --accent-secondary: #3b82f6;
            }

            body {
                font-family: "Outfit", sans-serif;
                background-color: var(--bg-body);
                background-image:
                    radial-gradient(circle at 15% 15%, rgba(139, 92, 246, 0.05) 0%, transparent 40%),
                    radial-gradient(circle at 85% 85%, rgba(59, 130, 246, 0.05) 0%, transparent 40%);
                color: var(--text-primary);
                height: 100vh;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                margin: 0;
            }

            .brand {
                font-family: "Dancing Script", cursive;
                font-size: 2.5rem;
                background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                margin-bottom: 2rem;
            }

            .error-card {
                background: var(--bg-card);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.05);
                border-radius: 20px;
                padding: 3rem;
                text-align: center;
                max-width: 480px;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            }

            h1 {
                font-size: 1.5rem;
                font-weight: 600;
                margin-bottom: 1rem;
            }

            p {
                color: var(--text-secondary);
                line-height: 1.6;
                margin-bottom: 2rem;
            }

            .btn {
                background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
                color: white;
                padding: 0.75rem 1.5rem;
                border-radius: 8px;
                text-decoration: none;
                font-weight: 600;
                transition: transform 0.2s;
                display: inline-block;
            }

            .btn:hover {
                transform: translateY(-2px);
            }
        </style>
    </head>

    <body>
        <div class="brand">Plugs</div>
        <div class="error-card">
            <h1>Critical System Failure</h1>
            <p>The application encountered a critical error and the error handler could not be loaded.</p>
            <a href="/" class="btn">Reload Application</a>
        </div>
    </body>

    </html>
    <?php
}
