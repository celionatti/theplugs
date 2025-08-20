<?php
declare(strict_types=1);

namespace Plugs\Console\Commands;

use Plugs\Console\Command;
use Plugs\Console\Support\Str;
use Plugs\Console\Support\Filesystem as FS;

class MakeControllerCommand extends Command
{
    protected string $description = 'Create a controller in app/Controllers with advanced options';

    private const CONTROLLER_TYPES = [
        'basic' => 'Basic Controller (index method only)',
        'resource' => 'Resource Controller (full CRUD methods)',
        'api' => 'API Resource Controller (without create/edit views)',
        'invokable' => 'Single Action Controller (__invoke method)'
    ];

    private const BASE_METHODS = [
        'index' => [
            'signature' => 'index()',
            'comment' => 'Display a listing of the resource'
        ]
    ];

    private const RESOURCE_METHODS = [
        'index' => [
            'signature' => 'index()',
            'comment' => 'Display a listing of the resource'
        ],
        'create' => [
            'signature' => 'create()',
            'comment' => 'Show the form for creating a new resource'
        ],
        'store' => [
            'signature' => 'store(Request $request)',
            'comment' => 'Store a newly created resource in storage'
        ],
        'show' => [
            'signature' => 'show(int $id)',
            'comment' => 'Display the specified resource'
        ],
        'edit' => [
            'signature' => 'edit(int $id)',
            'comment' => 'Show the form for editing the specified resource'
        ],
        'update' => [
            'signature' => 'update(Request $request, int $id)',
            'comment' => 'Update the specified resource in storage'
        ],
        'destroy' => [
            'signature' => 'destroy(int $id)',
            'comment' => 'Remove the specified resource from storage'
        ]
    ];

    private const API_METHODS = [
        'index' => [
            'signature' => 'index()',
            'comment' => 'Display a listing of the resource'
        ],
        'store' => [
            'signature' => 'store(Request $request)',
            'comment' => 'Store a newly created resource in storage'
        ],
        'show' => [
            'signature' => 'show(int $id)',
            'comment' => 'Display the specified resource'
        ],
        'update' => [
            'signature' => 'update(Request $request, int $id)',
            'comment' => 'Update the specified resource in storage'
        ],
        'destroy' => [
            'signature' => 'destroy(int $id)',
            'comment' => 'Remove the specified resource from storage'
        ]
    ];

    public function handle(): int
    {
        $this->output->header("Controller Generator");

        // Get and validate controller name
        $name = $this->getControllerName();
        $studlyName = Str::studly($name);
        
        if ($this->controllerExists($studlyName)) {
            return 1;
        }

        // Determine controller type
        $type = $this->determineControllerType();
        
        // Show generation info
        $this->displayGenerationInfo($studlyName, $type);

        // Generate controller with progress
        $filePath = $this->generateController($studlyName, $type);

        // Success feedback
        $this->displaySuccess($studlyName, $filePath, $type);

        return 0;
    }

    private function getControllerName(): string
    {
        $name = $this->argument('0');
        
        if (!$name) {
            $this->output->error("Controller name is required");
            $this->output->info("Usage: make:controller ControllerName [options]");
            exit(1);
        }

        // Clean and validate name
        $name = preg_replace('/Controller$/', '', $name);
        
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $name)) {
            $this->output->error("Invalid controller name: {$name}");
            $this->output->info("Controller name must start with a letter and contain only letters, numbers, and underscores");
            exit(1);
        }

        return $name;
    }

    private function controllerExists(string $studlyName): bool
    {
        $filePath = "app/Controllers/{$studlyName}.php";
        
        if (FS::exists($filePath)) {
            $this->output->warning("Controller already exists: {$filePath}");
            
            if (!$this->output->askConfirmation("Do you want to overwrite the existing controller?")) {
                $this->output->info("Controller generation cancelled");
                return true;
            }
            
            $this->output->info("Proceeding with overwrite...");
        }
        
        return false;
    }

    private function determineControllerType(): string
    {
        if ($this->option('invokable') || $this->option('i')) {
            return 'invokable';
        }
        
        if ($this->option('api') || $this->option('a')) {
            return 'api';
        }
        
        if ($this->option('resource') || $this->option('r')) {
            return 'resource';
        }
        
        return 'basic';
    }

    private function displayGenerationInfo(string $studlyName, string $type): void
    {
        $typeDescription = self::CONTROLLER_TYPES[$type];
        
        $this->output->box(
            "Controller: {$studlyName}\nType: {$typeDescription}\nLocation: app/Controllers/{$studlyName}.php",
            "Generation Details",
            "info"
        );
    }

    private function generateController(string $studlyName, string $type): string
    {
        $filePath = "app/Controllers/{$studlyName}.php";
        
        // Ensure directory exists
        $this->ensureDirectoryExists();
        
        // Generate with progress bar
        $this->output->progressBar(4, function($step) use ($studlyName, $type, $filePath) {
            switch($step) {
                case 1:
                    // Generate class structure
                    usleep(200000);
                    break;
                case 2:
                    // Generate methods
                    usleep(300000);
                    break;
                case 3:
                    // Write to file
                    $code = $this->generateControllerCode($studlyName, $type);
                    FS::put($filePath, $code);
                    break;
                case 4:
                    // Verify file creation
                    if (!FS::exists($filePath)) {
                        throw new \RuntimeException('Failed to create controller file');
                    }
                    break;
            }
        }, "Generating controller");

        return $filePath;
    }

    private function ensureDirectoryExists(): void
    {
        $dir = 'app/Controllers';
        if (!FS::isDirectory($dir)) {
            FS::makeDirectory($dir, 0755, true);
            $this->output->info("Created directory: {$dir}");
        }
    }

    private function generateControllerCode(string $studlyName, string $type): string
    {
        $methods = $this->generateMethods($type);
        $imports = $this->generateImports($type);
        
        $code = <<<PHP
        <?php
        declare(strict_types=1);

        namespace App\Controllers;
        {$imports}
        class {$studlyName}
        {{$methods}
        }
        PHP;

        return $code;
    }

    private function generateImports(string $type): string
    {
        $imports = [];
        
        if (in_array($type, ['resource', 'api'])) {
            $imports[] = "use Plugs\Http\Request;";
        }
        
        return empty($imports) ? '' : "\n" . implode("\n", $imports) . "\n";
    }

    private function generateMethods(string $type): string
    {
        $methods = [];
        
        switch ($type) {
            case 'invokable':
                $methods[] = $this->generateMethod('__invoke', '()', 'Handle the incoming request');
                break;
                
            case 'api':
                foreach (self::API_METHODS as $name => $config) {
                    $methods[] = $this->generateMethod($name, $config['signature'], $config['comment']);
                }
                break;
                
            case 'resource':
                foreach (self::RESOURCE_METHODS as $name => $config) {
                    $methods[] = $this->generateMethod($name, $config['signature'], $config['comment']);
                }
                break;
                
            case 'basic':
            default:
                foreach (self::BASE_METHODS as $name => $config) {
                    $methods[] = $this->generateMethod($name, $config['signature'], $config['comment']);
                }
                break;
        }
        
        return "\n" . implode("\n\n", $methods) . "\n";
    }

    private function generateMethod(string $name, string $signature, string $comment): string
    {
        // Extract method name from signature for special cases
        $methodName = $name === '__invoke' ? '__invoke' : $name;
        $fullSignature = str_replace($name . '(', $methodName . '(', $signature);
        
        return <<<METHOD
    /**
     * {$comment}
     */
    public function {$fullSignature}
    {
        // TODO: Implement {$methodName} method
    }
METHOD;
    }

    private function displaySuccess(string $studlyName, string $filePath, string $type): void
    {
        $this->output->success("Controller created successfully!");
        
        // Show method summary
        $methodCount = $this->getMethodCount($type);
        $typeDescription = self::CONTROLLER_TYPES[$type];
        
        $this->output->box(
            "Controller: {$studlyName}\nFile: {$filePath}\nType: {$typeDescription}\nMethods: {$methodCount}",
            "✅ Creation Summary",
            "success"
        );

        // Show next steps
        $this->displayNextSteps($studlyName, $type);
    }

    private function getMethodCount(string $type): int
    {
        return match($type) {
            'invokable' => 1,
            'basic' => count(self::BASE_METHODS),
            'api' => count(self::API_METHODS),
            'resource' => count(self::RESOURCE_METHODS),
            default => 1
        };
    }

    private function displayNextSteps(string $studlyName, string $type): void
    {
        $steps = [
            "Add your business logic to the controller methods",
            "Define routes for your controller actions"
        ];

        if ($type === 'resource') {
            $steps[] = "Consider creating corresponding views for CRUD operations";
            $steps[] = "Add validation rules for store() and update() methods";
        } elseif ($type === 'api') {
            $steps[] = "Implement JSON responses for API endpoints";
            $steps[] = "Add proper HTTP status codes";
        }

        $this->output->note("Next steps:\n• " . implode("\n• ", $steps));
        
        // Show example usage
        $this->displayUsageExample($studlyName, $type);
    }

    private function displayUsageExample(string $studlyName, string $type): void
    {
        $examples = [];
        
        switch ($type) {
            case 'resource':
                $examples = [
                    "Route::resource('items', {$studlyName}::class);",
                    "// Creates: GET /items, POST /items, GET /items/{id}, etc."
                ];
                break;
                
            case 'api':
                $examples = [
                    "Route::apiResource('api/items', {$studlyName}::class);",
                    "// Creates API routes without create/edit forms"
                ];
                break;
                
            case 'invokable':
                $examples = [
                    "Route::post('/action', {$studlyName}::class);",
                    "// Single action controller"
                ];
                break;
                
            case 'basic':
            default:
                $examples = [
                    "Route::get('/items', [{$studlyName}::class, 'index']);",
                    "// Basic controller route"
                ];
                break;
        }

        if (!empty($examples)) {
            $this->output->box(
                implode("\n", $examples),
                "Example Usage",
                "note"
            );
        }
    }
}