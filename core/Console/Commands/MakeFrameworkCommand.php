<?php
declare(strict_types=1);

namespace Plugs\Console\Commands;

use Plugs\Console\Command;
use Plugs\Console\Support\Filesystem as FS;

class MakeFrameworkCommand extends Command
{
    protected string $description = 'Generate comprehensive framework structure with starter files';

    private const FRAMEWORK_STRUCTURE = [
        // Application directories
        'app' => [
            'Controllers' => 'Application controllers',
            'Models' => 'Data models and entities',
            'Services' => 'Business logic services',
            'Middleware' => 'HTTP middleware',
            'Requests' => 'Form request validation',
            'Resources' => 'API resource transformers',
            'Events' => 'Application events',
            'Listeners' => 'Event listeners',
            'Jobs' => 'Background jobs',
            'Mail' => 'Email templates and classes',
            'Notifications' => 'User notifications',
            'Policies' => 'Authorization policies',
            'Providers' => 'Service providers',
            'Rules' => 'Custom validation rules',
            'Traits' => 'Reusable model traits',
        ],
        
        // Configuration
        'config' => [
            '' => 'Application configuration files'
        ],
        
        // Database
        'database' => [
            'migrations' => 'Database schema migrations',
            'seeders' => 'Database seeders',
            'factories' => 'Model factories for testing'
        ],
        
        // Resources
        'resources' => [
            'views' => 'Application views and templates',
            'assets' => [
                'css' => 'Stylesheets',
                'js' => 'JavaScript files',
                'images' => 'Image assets',
                'fonts' => 'Font files'
            ],
            'lang' => 'Language files for i18n'
        ],
        
        // Public web root
        'public' => [
            'css' => 'Compiled CSS files',
            'js' => 'Compiled JavaScript files',
            'images' => 'Public images',
            'uploads' => 'User uploaded files'
        ],
        
        // Routes
        'routes' => [
            '' => 'Application route definitions'
        ],
        
        // Storage
        'storage' => [
            'app' => [
                'public' => 'Publicly accessible files',
                'private' => 'Private application files'
            ],
            'framework' => [
                'cache' => 'Framework cache files',
                'sessions' => 'Session files',
                'views' => 'Compiled view files'
            ],
            'logs' => 'Application log files'
        ],
        
        // Testing
        'tests' => [
            'Feature' => 'Feature tests',
            'Unit' => 'Unit tests'
        ]
    ];

    private const STARTER_FILES = [
        'routes/web.php' => [
            'description' => 'Web routes configuration',
            'content' => '<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
| These routes are loaded by the RouteServiceProvider within a group
| which contains the "web" middleware group.
|
*/

return [
    [\'GET\', \'/\', \'HomeController@index\', \'home\'],
    [\'GET\', \'/about\', \'HomeController@about\', \'about\'],
    [\'GET\', \'/contact\', \'HomeController@contact\', \'contact\'],
];
'
        ],
        
        'routes/api.php' => [
            'description' => 'API routes configuration',
            'content' => '<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider within a group
| which is assigned the "api" middleware group.
|
*/

return [
    [\'GET\', \'/api/health\', \'Api\\HealthController@check\', \'api.health\'],
    [\'GET\', \'/api/ping\', \'Api\\PingController@ping\', \'api.ping\'],
    [\'GET\', \'/api/version\', \'Api\\SystemController@version\', \'api.version\'],
];
'
        ],
        
        'config/app.php' => [
            'description' => 'Application configuration',
            'content' => '<?php
declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    */
    \'name\' => env(\'APP_NAME\', \'Plugs Framework\'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    */
    \'env\' => env(\'APP_ENV\', \'production\'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    */
    \'debug\' => env(\'APP_DEBUG\', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    */
    \'url\' => env(\'APP_URL\', \'http://localhost\'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    */
    \'timezone\' => \'UTC\',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    */
    \'locale\' => \'en\',
    \'fallback_locale\' => \'en\',
];
'
        ],
        
        'config/database.php' => [
            'description' => 'Database configuration',
            'content' => '<?php
declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    */
    \'default\' => env(\'DB_CONNECTION\', \'mysql\'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    */
    \'connections\' => [
        \'mysql\' => [
            \'driver\' => \'mysql\',
            \'host\' => env(\'DB_HOST\', \'127.0.0.1\'),
            \'port\' => env(\'DB_PORT\', \'3306\'),
            \'database\' => env(\'DB_DATABASE\', \'forge\'),
            \'username\' => env(\'DB_USERNAME\', \'forge\'),
            \'password\' => env(\'DB_PASSWORD\', \'\'),
            \'charset\' => \'utf8mb4\',
            \'collation\' => \'utf8mb4_unicode_ci\',
            \'prefix\' => \'\',
            \'strict\' => true,
        ],
        
        \'sqlite\' => [
            \'driver\' => \'sqlite\',
            \'database\' => env(\'DB_DATABASE\', database_path(\'database.sqlite\')),
            \'prefix\' => \'\',
        ],
    ],
];
'
        ],
        
        'app/Controllers/HomeController.php' => [
            'description' => 'Default home controller',
            'content' => '<?php
declare(strict_types=1);

namespace App\Controllers;

class HomeController
{
    /**
     * Display the home page
     */
    public function index()
    {
        return view(\'home\', [
            \'title\' => \'Welcome to Plugs Framework\',
            \'message\' => \'Your application is ready!\'
        ]);
    }

    /**
     * Display the about page
     */
    public function about()
    {
        return view(\'about\', [
            \'title\' => \'About Us\'
        ]);
    }

    /**
     * Display the contact page
     */
    public function contact()
    {
        return view(\'contact\', [
            \'title\' => \'Contact Us\'
        ]);
    }
}
'
        ],
        
        'app/Controllers/Api/HealthController.php' => [
            'description' => 'API health check controller',
            'content' => '<?php
declare(strict_types=1);

namespace App\Controllers\Api;

class HealthController
{
    /**
     * System health check endpoint
     */
    public function check()
    {
        return [
            \'status\' => \'ok\',
            \'timestamp\' => time(),
            \'uptime\' => $this->getUptime(),
            \'memory\' => [
                \'used\' => memory_get_usage(true),
                \'peak\' => memory_get_peak_usage(true)
            ]
        ];
    }

    private function getUptime(): array
    {
        $uptime = time() - $_SERVER[\'REQUEST_TIME\'];
        return [
            \'seconds\' => $uptime,
            \'formatted\' => gmdate(\'H:i:s\', $uptime)
        ];
    }
}
'
        ],
        
        'app/Controllers/Api/PingController.php' => [
            'description' => 'API ping controller',
            'content' => '<?php
declare(strict_types=1);

namespace App\Controllers\Api;

class PingController
{
    /**
     * Simple ping endpoint
     */
    public function ping()
    {
        return [
            \'message\' => \'pong\',
            \'timestamp\' => date(\'Y-m-d H:i:s\'),
            \'server\' => $_SERVER[\'HTTP_HOST\'] ?? \'localhost\'
        ];
    }
}
'
        ],
        
        'resources/views/layout.php' => [
            'description' => 'Base layout template',
            'content' => '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? \'Plugs Framework\' ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .content {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?= $title ?? \'Plugs Framework\' ?></h1>
    </div>
    
    <div class="content">
        <?= $content ?? \'\' ?>
    </div>
</body>
</html>
'
        ],
        
        'resources/views/home.php' => [
            'description' => 'Home page template',
            'content' => '<?php
$content = \'
<h2>Welcome to Your New Application!</h2>
<p>\' . ($message ?? \'Your application is running successfully.\') . \'</p>

<h3>Quick Start</h3>
<ul>
    <li>Edit routes in <code>routes/web.php</code></li>
    <li>Create controllers with <code>php console make:controller</code></li>
    <li>Create models with <code>php console make:model</code></li>
    <li>View this template in <code>resources/views/home.php</code></li>
</ul>

<h3>API Endpoints</h3>
<ul>
    <li><a href="/api/health">/api/health</a> - System health check</li>
    <li><a href="/api/ping">/api/ping</a> - Simple ping test</li>
</ul>
\';

include __DIR__ . \'/layout.php\';
'
        ],
        
        '.env.example' => [
            'description' => 'Environment variables example',
            'content' => '# Application
APP_NAME="Plugs Framework"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=plugs_framework
DB_USERNAME=root
DB_PASSWORD=

# Cache
CACHE_DRIVER=file

# Session
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Mail
MAIL_MAILER=smtp
MAIL_HOST=localhost
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
'
        ],
        
        '.gitignore' => [
            'description' => 'Git ignore file',
            'content' => '# Dependencies
/vendor/
/node_modules/

# Environment
.env
.env.local
.env.production

# IDE
.idea/
.vscode/
*.swp
*.swo

# OS
.DS_Store
Thumbs.db

# Logs
/storage/logs/*.log

# Cache
/storage/framework/cache/*
/storage/framework/sessions/*
/storage/framework/views/*

# Compiled assets
/public/css/*
/public/js/*
/public/mix-manifest.json

# Testing
/coverage/
.phpunit.result.cache
'
        ],
        
        'README.md' => [
            'description' => 'Project documentation',
            'content' => '# Plugs Framework Application

A modern PHP framework application built with the Plugs Framework.

## Quick Start

1. **Install dependencies** (if using Composer):
   ```bash
   composer install
   ```

2. **Set up environment**:
   ```bash
   cp .env.example .env
   php console key:generate
   ```

3. **Configure database** in `.env` file

4. **Start development server**:
   ```bash
   php -S localhost:8000 -t public
   ```

5. **Visit your application**: http://localhost:8000

## Available Commands

- `php console make:controller ControllerName` - Create a new controller
- `php console make:model ModelName` - Create a new model
- `php console key:generate` - Generate application key

## Directory Structure

```
├── app/                    # Application code
│   ├── Controllers/        # HTTP controllers
│   ├── Models/            # Data models
│   └── Services/          # Business logic
├── config/                # Configuration files
├── public/                # Public web files
├── resources/             # Views, assets, language files
├── routes/                # Route definitions
├── storage/               # Storage files
└── tests/                 # Test files
```

## API Endpoints

- `GET /api/health` - System health check
- `GET /api/ping` - Simple ping test

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

This project is open-sourced software licensed under the MIT license.
'
        ]
    ];

    public function handle(): int
    {
        $this->output->header("Framework Structure Generator");
        
        // Check for existing framework structure
        if ($this->hasExistingStructure()) {
            if (!$this->confirmOverwrite()) {
                return 0;
            }
        }

        // Show what will be created
        $this->displayGenerationPlan();

        // Create directory structure
        $this->createDirectoryStructure();

        // Create starter files
        $this->createStarterFiles();

        // Create additional helpful files
        $this->createAdditionalFiles();

        // Show success summary
        $this->displaySuccessSummary();

        return 0;
    }

    private function hasExistingStructure(): bool
    {
        $coreDirectories = ['app', 'config', 'routes', 'public', 'resources'];
        
        foreach ($coreDirectories as $dir) {
            if (FS::isDirectory($dir)) {
                return true;
            }
        }
        
        return false;
    }

    private function confirmOverwrite(): bool
    {
        $this->output->warning("Framework structure already exists in this directory");
        return $this->output->askConfirmation("Do you want to continue and potentially overwrite files?");
    }

    private function displayGenerationPlan(): void
    {
        $totalDirs = $this->countDirectories(self::FRAMEWORK_STRUCTURE);
        $totalFiles = count(self::STARTER_FILES);
        
        $this->output->box(
            "Directories: {$totalDirs}\nStarter Files: {$totalFiles}\nAdditional Files: 3 (README.md, .env.example, .gitignore)",
            "Generation Plan",
            "info"
        );
    }

    private function countDirectories(array $structure, int $count = 0): int
    {
        foreach ($structure as $name => $content) {
            $count++;
            if (is_array($content)) {
                $count = $this->countDirectories($content, $count);
            }
        }
        return $count;
    }

    private function createDirectoryStructure(): void
    {
        $this->output->subHeader("Creating Directory Structure");
        
        $totalDirs = $this->countDirectories(self::FRAMEWORK_STRUCTURE);
        $current = 0;
        
        $this->output->progressBar($totalDirs, function($step) use (&$current) {
            $this->createDirectoriesRecursive(self::FRAMEWORK_STRUCTURE, '', $current, $step);
            usleep(50000); // Small delay for visual effect
        }, "Creating directories");
    }

    private function createDirectoriesRecursive(array $structure, string $basePath = '', int &$current = 0, int $step = 0): void
    {
        foreach ($structure as $name => $content) {
            $current++;
            
            if ($current > $step) {
                break;
            }
            
            $path = $basePath ? $basePath . '/' . $name : $name;
            
            if (is_array($content)) {
                if (!empty($name)) {
                    FS::ensureDir($path);
                    $this->createDirectoriesRecursive($content, $path, $current, $step);
                } else {
                    // Root level directory
                    $this->createDirectoriesRecursive($content, $basePath, $current, $step);
                }
            } else {
                // Create directory with description
                FS::ensureDir($path);
            }
        }
    }

    private function createStarterFiles(): void
    {
        $this->output->subHeader("Creating Starter Files");
        
        $total = count(self::STARTER_FILES);
        $this->output->progressBar($total, function($step) {
            $files = array_keys(self::STARTER_FILES);
            $filePath = $files[$step - 1];
            $fileInfo = self::STARTER_FILES[$filePath];
            
            // Ensure directory exists
            $dir = dirname($filePath);
            if ($dir !== '.') {
                FS::ensureDir($dir);
            }
            
            // Create file if it doesn't exist
            if (!FS::exists($filePath)) {
                FS::put($filePath, $fileInfo['content']);
            }
            
            usleep(100000); // Visual delay
        }, "Creating starter files");
    }

    private function createAdditionalFiles(): void
    {
        $this->output->info("Creating additional project files...");
        
        // Create basic composer.json if it doesn't exist
        if (!FS::exists('composer.json')) {
            $composerContent = json_encode([
                'name' => 'plugs/framework-app',
                'description' => 'A Plugs Framework application',
                'type' => 'project',
                'require' => [
                    'php' => '^8.0'
                ],
                'autoload' => [
                    'psr-4' => [
                        'App\\' => 'app/'
                    ]
                ],
                'minimum-stability' => 'stable',
                'prefer-stable' => true
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            
            FS::put('composer.json', $composerContent);
        }

        // Create basic package.json for frontend assets
        if (!FS::exists('package.json')) {
            $packageContent = json_encode([
                'name' => 'plugs-framework-app',
                'version' => '1.0.0',
                'description' => 'Frontend assets for Plugs Framework application',
                'main' => 'index.js',
                'scripts' => [
                    'dev' => 'echo "Add your build commands here"',
                    'build' => 'echo "Add your production build commands here"'
                ],
                'devDependencies' => [],
                'dependencies' => []
            ], JSON_PRETTY_PRINT);
            
            FS::put('package.json', $packageContent);
        }
    }

    private function displaySuccessSummary(): void
    {
        $this->output->banner("FRAMEWORK READY!");
        
        $this->output->success("Framework structure created successfully!");
        
        // Count created items
        $totalDirs = $this->countDirectories(self::FRAMEWORK_STRUCTURE);
        $totalFiles = count(self::STARTER_FILES) + 3; // +3 for additional files
        
        $this->output->box(
            "✅ {$totalDirs} directories created\n✅ {$totalFiles} files generated\n✅ Project structure ready\n✅ Starter templates included",
            "Creation Summary",
            "success"
        );

        // Show next steps
        $this->displayNextSteps();
        
        // Show useful commands
        $this->displayUsefulCommands();
    }

    private function displayNextSteps(): void
    {
        $steps = [
            "Copy .env.example to .env and configure your settings",
            "Generate an application key: php console key:generate",
            "Configure your database connection in .env",
            "Start coding in the app/ directory",
            "Visit http://localhost:8000 after starting a dev server"
        ];

        $this->output->note("Next steps:\n• " . implode("\n• ", $steps));
    }

    private function displayUsefulCommands(): void
    {
        $commands = [
            "# Start development server",
            "php -S localhost:8000 -t public",
            "",
            "# Generate application components",
            "php console make:controller HomeController",
            "php console make:model User",
            "php console key:generate",
            "",
            "# Install dependencies (if using Composer)",
            "composer install"
        ];

        $this->output->box(
            implode("\n", $commands),
            "Useful Commands",
            "note"
        );
    }
}