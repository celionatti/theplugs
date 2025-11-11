<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Public Index File
|--------------------------------------------------------------------------
|
| This is the entry point to your application. You can use this file
| to initialize and run your application.
*/

/*
 |----------------------------------------------------------------------
 | Load the Bootstrap File
 |----------------------------------------------------------------------
 |
 | Here we are loading the bootstrap file to set up the application.
 */
require __DIR__ . '/../bootstrap/boot.php';

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
} catch (Exception $e) {
    if ($_ENV['APP_ENV'] === 'local' && $_ENV['APP_DEBUG'] ?? false) {
        renderDebugErrorPage($e);
    } else {
        renderProductionErrorPage($e);
    }
}
