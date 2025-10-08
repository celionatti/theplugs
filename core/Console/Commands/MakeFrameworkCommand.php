<?php

declare(strict_types=1);

namespace Plugs\Console\Commands;

use Plugs\Console\Command;
use Plugs\Console\Support\Filesystem as FS;

class MakeFrameworkCommand extends Command
{
    protected string $description = 'Generate comprehensive framework structure with starter files';

    /**
     * Base path for framework templates
     */
    private const TEMPLATES_PATH = __DIR__ . '/../templates/framework';

    private const FRAMEWORK_STRUCTURE = [
        // Application directories
        'app' => [
            'Controllers' => 'Application controllers',
            'Models' => 'Data models and entities',
            'Middleware' => 'HTTP middleware',
            'Requests' => 'Form request validation',
            'Providers' => 'Service providers',
        ],

        // Configuration
        'config' => [
            'app' => 'Application configuration for core settings',
            'middleware' => 'Application configuration for middleware',
            'seo' => 'Application configuration for SEO',
            'view' => 'Application configuration for views',
            'session' => 'Application configuration for session',
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
            'lang' => 'Language files for i18n'
        ],

        // Public web root
        'public' => [
            'assets' => [
                'css' => 'Stylesheets',
                'js' => 'JavaScript files',
                'img' => 'Image assets',
                'fonts' => 'Font files',
            ],
            'uploads' => 'User uploaded files',
            'index.php' => 'Front controller file',
            '.htaccess' => 'Apache configuration file (if using Apache)'
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

    /**
     * Template files to copy from templates directory
     */
    private const TEMPLATE_FILES = [
        // Routes
        'routes/web.php' => 'routes/web.php.stub',

        // Config
        'config/app.php' => 'config/app.php.stub',
        'config/seo.php' => 'config/seo.php.stub',
        'config/middleware.php' => 'config/middleware.php.stub',
        'config/view.php' => 'config/view.php.stub',
        'config/session.php' => 'config/session.php.stub',

        // Controllers
        'app/Controllers/HomeController.php' => 'app/Controllers/HomeController.php.stub',

        // Views
        'resources/views/layouts/default.plug.php' => 'resources/views/layouts/default.php.stub',
        'resources/views/welcome.plug.php' => 'resources/views/welcome.php.stub',

        // Public assets
        'public/.htaccess' => 'public/.htaccess.stub',
        'public/index.php' => 'public/index.php.stub',
        'public/assets/css/global.css' => 'public/assets/css/global.css.stub',

        // Project files
        '.env.example' => 'root/.env.example.stub',
        '.gitignore' => 'root/.gitignore.stub',
        'README.md' => 'root/README.md.stub',
        'theplugs' => 'root/theplugs.stub',
    ];

    public function handle(): int
    {
        $this->output->header("Framework Structure Generator");

        // Verify templates exist
        if (!$this->verifyTemplatesExist()) {
            $this->output->error("Template files not found at: " . self::TEMPLATES_PATH);
            $this->output->note("Please ensure the templates directory exists and contains all required stub files.");
            return 1;
        }

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

        // Create starter files from templates
        $this->createStarterFiles();

        // Create additional helpful files
        $this->createAdditionalFiles();

        // Show success summary
        $this->displaySuccessSummary();

        return 0;
    }

    /**
     * Verify that template files exist
     */
    private function verifyTemplatesExist(): bool
    {
        if (!is_dir(self::TEMPLATES_PATH)) {
            return false;
        }

        $missingFiles = [];
        foreach (self::TEMPLATE_FILES as $templatePath) {
            $fullPath = self::TEMPLATES_PATH . '/' . $templatePath;
            if (!file_exists($fullPath)) {
                $missingFiles[] = $templatePath;
            }
        }

        if (!empty($missingFiles)) {
            $this->output->warning("Missing template files:");
            foreach ($missingFiles as $file) {
                $this->output->line("  - {$file}");
            }
            return false;
        }

        return true;
    }

    /**
     * Load template content from file
     */
    private function loadTemplate(string $templatePath): string
    {
        $fullPath = self::TEMPLATES_PATH . '/' . $templatePath;

        if (!file_exists($fullPath)) {
            throw new \RuntimeException("Template not found: {$templatePath}");
        }

        return file_get_contents($fullPath);
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
        $totalFiles = count(self::TEMPLATE_FILES);

        $this->output->box(
            "Directories: {$totalDirs}\nStarter Files: {$totalFiles}\nAdditional Files: 2 (composer.json, package.json)",
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

        $this->output->progressBar($totalDirs, function ($step) use (&$current) {
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

        $total = count(self::TEMPLATE_FILES);
        $this->output->progressBar($total, function ($step) {
            $files = array_keys(self::TEMPLATE_FILES);
            $targetPath = $files[$step - 1];
            $templatePath = self::TEMPLATE_FILES[$targetPath];

            // Ensure directory exists
            $dir = dirname($targetPath);
            if ($dir !== '.') {
                FS::ensureDir($dir);
            }

            // Create file from template if it doesn't exist
            if (!FS::exists($targetPath)) {
                $content = $this->loadTemplate($templatePath);
                FS::put($targetPath, $content);
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
                    'php' => '^7.0'
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
        $totalFiles = count(self::TEMPLATE_FILES) + 2; // +2 for additional files

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
            "php theplugs make:controller HomeController",
            "php theplugs make:model User",
            "php theplugs key:generate",
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
